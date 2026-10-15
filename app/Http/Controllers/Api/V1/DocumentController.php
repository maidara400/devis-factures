<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\DocumentRequest;
use App\Http\Requests\DocumentStatusRequest;
use App\Http\Resources\DocumentResource;
use App\Jobs\SendDocumentJob;
use App\Models\Document;
use App\Services\DocumentPdfService;
use App\Services\DocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

/**
 * Devis et factures. Un devis (`type=quote`) et une facture (`type=invoice`) partagent la même structure ;
 * le numéro est généré automatiquement (`DEV-2026-0001`, `FAC-2026-0001`).
 *
 * @tags Devis & Factures
 */
class DocumentController extends Controller
{
    public function __construct(private readonly DocumentService $documents) {}

    /**
     * Lister les documents
     *
     * Paginé (15 par page), du plus récent au plus ancien.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'type' => ['nullable', Rule::enum(DocumentType::class)],
            'status' => ['nullable', Rule::enum(DocumentStatus::class)],
            'client_id' => ['nullable', 'integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $documents = Document::query()
            ->with('client')
            ->filter($filters)
            ->latest('issue_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return DocumentResource::collection($documents);
    }

    /**
     * Créer un devis ou une facture (brouillon)
     *
     * Opération transactionnelle : numéro, en-tête, lignes et totaux sont créés ensemble ou pas du tout.
     * Pour une ligne liée à un `article_id`, description, prix et TVA sont repris de l'article s'ils sont omis.
     */
    public function store(DocumentRequest $request): JsonResponse
    {
        $document = $this->documents->create($request->validated(), $request->user());

        return (new DocumentResource($document))->response()->setStatusCode(201);
    }

    /**
     * Afficher un document avec ses lignes
     */
    public function show(Document $document): DocumentResource
    {
        return new DocumentResource($document->load(['client', 'lines', 'user']));
    }

    /**
     * Modifier un brouillon
     *
     * Les lignes envoyées remplacent les lignes existantes. Refusé (422) si le document n'est plus un brouillon.
     */
    public function update(DocumentRequest $request, Document $document): DocumentResource
    {
        return new DocumentResource($this->documents->update($document, $request->validated()));
    }

    /**
     * Supprimer un brouillon
     */
    public function destroy(Document $document): JsonResponse
    {
        $this->documents->delete($document);

        return response()->json(null, 204);
    }

    /**
     * Changer le statut
     *
     * Transitions autorisées : `draft → sent`, `sent → paid`, `sent → draft`. Pour un devis, `paid` signifie « accepté ».
     */
    public function updateStatus(DocumentStatusRequest $request, Document $document): DocumentResource
    {
        $document = $this->documents->changeStatus($document, DocumentStatus::from($request->validated('status')));

        return new DocumentResource($document->load(['client', 'lines']));
    }

    /**
     * Convertir un devis en facture
     *
     * Crée une facture brouillon (échéance à 30 jours) à partir d'un devis envoyé ou accepté. Une seule facture par devis.
     */
    public function convertToInvoice(Document $document): JsonResponse
    {
        $invoice = $this->documents->convertToInvoice($document);

        return (new DocumentResource($invoice->load(['client', 'lines'])))->response()->setStatusCode(201);
    }

    /**
     * Télécharger le PDF
     *
     * @response 200 binary PDF
     */
    public function pdf(Document $document, DocumentPdfService $pdf): Response
    {
        return response($pdf->render($document), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$pdf->filename($document).'"',
        ]);
    }

    /**
     * Envoyer par e-mail
     *
     * Met en file d'attente l'envoi du PDF au client (job asynchrone). Le document passe en « envoyé » une fois le mail parti.
     */
    public function send(Document $document): JsonResponse
    {
        if (! $document->client->email) {
            return response()->json(['message' => "Le client n'a pas d'adresse e-mail."], 422);
        }

        SendDocumentJob::dispatch($document);

        return response()->json(['message' => 'Envoi programmé.'], 202);
    }
}
