<?php

namespace App\Http\Controllers\Web;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\DocumentRequest;
use App\Http\Requests\DocumentStatusRequest;
use App\Jobs\SendDocumentJob;
use App\Models\Article;
use App\Models\Client;
use App\Models\Document;
use App\Services\DocumentCsvExporter;
use App\Services\DocumentPdfService;
use App\Services\DocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function __construct(private readonly DocumentService $documents) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['type', 'status', 'client_id', 'from', 'to', 'search']);

        return view('documents.index', [
            'documents' => Document::with('client')
                ->filter($filters)
                ->latest('issue_date')->latest('id')
                ->paginate(12)
                ->withQueryString(),
            'filters' => $filters,
        ]);
    }

    public function export(Request $request, DocumentCsvExporter $exporter): StreamedResponse
    {
        $filters = $request->only(['type', 'status', 'client_id', 'from', 'to', 'search']);

        return response()->streamDownload(
            fn () => $exporter->write(fopen('php://output', 'w'), $filters),
            'documents-'.now()->format('Ymd-His').'.csv',
            ['Content-Type' => 'text/csv; charset=UTF-8'],
        );
    }

    public function create(Request $request): View
    {
        $type = DocumentType::tryFrom((string) $request->query('type')) ?? DocumentType::Quote;

        return view('documents.form', [
            'document' => new Document([
                'type' => $type,
                'client_id' => $request->query('client_id'),
                'issue_date' => today(),
                'due_date' => today()->addDays(30),
            ]),
            'lines' => [],
            ...$this->formData(),
        ]);
    }

    public function store(DocumentRequest $request): RedirectResponse
    {
        $document = $this->documents->create($request->validated(), $request->user());

        return redirect()->route('documents.show', $document)
            ->with('success', "{$document->type->label()} {$document->number} créé en brouillon.");
    }

    public function show(Document $document): View
    {
        return view('documents.show', [
            'document' => $document->load(['client', 'lines', 'user', 'sourceQuote', 'invoice']),
        ]);
    }

    public function edit(Document $document): View|RedirectResponse
    {
        if (! $document->isDraft()) {
            return redirect()->route('documents.show', $document)->with('error', 'Seul un brouillon peut être modifié.');
        }

        return view('documents.form', [
            'document' => $document,
            'lines' => $document->lines->map(fn ($l) => [
                'key' => 'l'.$l->id,
                'article_id' => $l->article_id ?? '',
                'description' => $l->description,
                'quantity' => (float) $l->quantity,
                'unit_price' => (float) $l->unit_price,
                'tax_rate' => (float) $l->tax_rate,
            ])->all(),
            ...$this->formData(),
        ]);
    }

    public function update(DocumentRequest $request, Document $document): RedirectResponse
    {
        $this->documents->update($document, $request->validated());

        return redirect()->route('documents.show', $document)->with('success', 'Document mis à jour.');
    }

    public function destroy(Document $document): RedirectResponse
    {
        $this->documents->delete($document);

        return redirect()->route('documents.index')->with('success', 'Brouillon supprimé.');
    }

    public function updateStatus(DocumentStatusRequest $request, Document $document): RedirectResponse
    {
        $document = $this->documents->changeStatus($document, DocumentStatus::from($request->validated('status')));

        return back()->with('success', "Statut : {$document->status->label($document->type)}.");
    }

    public function convert(Document $document): RedirectResponse
    {
        $invoice = $this->documents->convertToInvoice($document);

        return redirect()->route('documents.show', $invoice)->with('success', "Facture {$invoice->number} créée à partir du devis.");
    }

    public function send(Document $document): RedirectResponse
    {
        if (! $document->client->email) {
            return back()->with('error', "Ce client n'a pas d'adresse e-mail.");
        }

        SendDocumentJob::dispatch($document);

        return back()->with('success', "Envoi à {$document->client->email} programmé.");
    }

    public function pdf(Request $request, Document $document, DocumentPdfService $pdf): Response
    {
        return response($pdf->render($document), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => ($request->boolean('download') ? 'attachment' : 'inline')
                .'; filename="'.$pdf->filename($document).'"',
        ]);
    }

    /**
     * @return array{clients: mixed, articles: mixed}
     */
    private function formData(): array
    {
        return [
            'clients' => Client::orderBy('name')->get(['id', 'name']),
            'articles' => Article::where('is_active', true)->orderBy('name')
                ->get(['id', 'reference', 'name', 'unit_price', 'tax_rate']),
        ];
    }
}
