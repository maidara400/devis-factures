<?php

namespace App\Jobs;

use App\Enums\DocumentStatus;
use App\Mail\DocumentMail;
use App\Models\Document;
use App\Services\DocumentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

/**
 * Génère le PDF, l'envoie par e-mail au client, puis passe le document en « Envoyé ».
 * Exécuté en file d'attente : `php artisan queue:work`.
 */
class SendDocumentJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [30, 120];

    public function __construct(public readonly Document $document) {}

    public function handle(DocumentService $service): void
    {
        $document = $this->document->fresh(['client', 'lines']);

        if (! $document || ! $document->client->email) {
            $this->fail(new \RuntimeException('Le client n\'a pas d\'adresse e-mail.'));

            return;
        }

        Mail::to($document->client->email)->send(new DocumentMail($document));

        if ($document->isDraft()) {
            $service->changeStatus($document, DocumentStatus::Sent);
        }
    }
}
