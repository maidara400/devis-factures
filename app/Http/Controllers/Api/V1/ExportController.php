<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Http\Controllers\Controller;
use App\Http\Resources\ExportResource;
use App\Jobs\ExportDocumentsCsvJob;
use App\Models\Export;
use App\Services\DocumentCsvExporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Export CSV des devis et factures (séparateur `;`, encodage UTF-8 avec BOM).
 *
 * @tags Export CSV
 */
class ExportController extends Controller
{
    /**
     * Télécharger un export immédiat
     *
     * Flux CSV direct, adapté aux volumes modestes. Pour de gros volumes, utiliser `POST /exports`.
     * Mêmes filtres que `GET /documents`.
     *
     * @response 200 binary CSV
     */
    public function stream(Request $request, DocumentCsvExporter $exporter): StreamedResponse
    {
        $filters = $this->filters($request);

        return response()->streamDownload(
            fn () => $exporter->write(fopen('php://output', 'w'), $filters),
            'documents-'.now()->format('Ymd-His').'.csv',
            ['Content-Type' => 'text/csv; charset=UTF-8'],
        );
    }

    /**
     * Programmer un export en arrière-plan
     *
     * Retourne `202` : interroger `GET /exports/{id}` jusqu'à `status = completed`, puis télécharger via `download_url`.
     */
    public function store(Request $request): JsonResponse
    {
        $export = Export::create([
            'user_id' => $request->user()->id,
            'status' => Export::PENDING,
            'filters' => $this->filters($request),
        ]);

        ExportDocumentsCsvJob::dispatch($export);

        return (new ExportResource($export))->response()->setStatusCode(202);
    }

    /**
     * Suivre un export
     */
    public function show(Request $request, Export $export): ExportResource
    {
        $this->authorizeOwner($request, $export);

        return new ExportResource($export);
    }

    /**
     * Télécharger le fichier d'un export terminé
     *
     * @response 200 binary CSV
     */
    public function download(Request $request, Export $export): StreamedResponse|JsonResponse
    {
        $this->authorizeOwner($request, $export);

        if (! $export->isCompleted() || ! Storage::disk('local')->exists($export->path)) {
            return response()->json(['message' => "L'export n'est pas prêt."], 409);
        }

        return Storage::disk('local')->download($export->path, basename($export->path), [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function authorizeOwner(Request $request, Export $export): void
    {
        abort_unless($export->user_id === $request->user()->id || $request->user()->isAdmin(), 403);
    }

    /**
     * @return array<string, mixed>
     */
    private function filters(Request $request): array
    {
        return $request->validate([
            'type' => ['nullable', Rule::enum(DocumentType::class)],
            'status' => ['nullable', Rule::enum(DocumentStatus::class)],
            'client_id' => ['nullable', 'integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);
    }
}
