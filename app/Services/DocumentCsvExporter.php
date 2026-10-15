<?php

namespace App\Services;

use App\Models\Document;

/**
 * Écrit les documents filtrés au format CSV (séparateur `;`, BOM UTF-8 pour une ouverture directe dans Excel).
 */
class DocumentCsvExporter
{
    public const HEADERS = [
        'Numéro', 'Type', 'Statut', 'Client', 'Date émission', 'Échéance',
        'Total HT', 'TVA', 'Total TTC', 'Envoyé le', 'Payé le',
    ];

    /**
     * @param  resource  $stream
     * @param  array<string, mixed>  $filters
     * @return int Nombre de lignes exportées
     */
    public function write($stream, array $filters = []): int
    {
        fwrite($stream, "\xEF\xBB\xBF");
        fputcsv($stream, self::HEADERS, ';');

        $count = 0;

        Document::query()
            ->with('client')
            ->filter($filters)
            ->orderBy('issue_date')
            ->orderBy('id')
            ->chunk(500, function ($documents) use ($stream, &$count) {
                foreach ($documents as $document) {
                    fputcsv($stream, [
                        $document->number,
                        $document->type->label(),
                        $document->status->label($document->type),
                        $document->client->name,
                        $document->issue_date->format('d/m/Y'),
                        $document->due_date?->format('d/m/Y'),
                        $document->subtotal,
                        $document->tax_total,
                        $document->total,
                        $document->sent_at?->format('d/m/Y H:i'),
                        $document->paid_at?->format('d/m/Y H:i'),
                    ], ';');
                    $count++;
                }
            });

        return $count;
    }
}
