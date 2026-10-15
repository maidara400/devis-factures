<?php

namespace App\Jobs;

use App\Models\Export;
use App\Services\DocumentCsvExporter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Génère l'export CSV en arrière-plan (gros volumes) et le dépose dans storage/app/private/exports.
 */
class ExportDocumentsCsvJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public readonly Export $export) {}

    public function handle(DocumentCsvExporter $exporter): void
    {
        $this->export->update(['status' => Export::PROCESSING]);

        $path = sprintf('exports/documents-%s-%d.csv', now()->format('Ymd-His'), $this->export->id);
        $stream = fopen('php://temp', 'w+');

        try {
            $rows = $exporter->write($stream, $this->export->filters ?? []);
            rewind($stream);
            Storage::disk('local')->put($path, $stream);
        } finally {
            fclose($stream);
        }

        $this->export->update([
            'status' => Export::COMPLETED,
            'path' => $path,
            'rows_count' => $rows,
            'completed_at' => now(),
        ]);
    }

    public function failed(Throwable $e): void
    {
        $this->export->update(['status' => Export::FAILED, 'error' => $e->getMessage()]);
    }
}
