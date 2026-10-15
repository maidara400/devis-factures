<?php

namespace App\Mail;

use App\Models\Document;
use App\Services\DocumentPdfService;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class DocumentMail extends Mailable
{
    public function __construct(public readonly Document $document) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf('%s %s - %s', $this->document->type->label(), $this->document->number, config('company.name')),
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.document');
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $pdf = app(DocumentPdfService::class);

        return [
            Attachment::fromData(fn () => $pdf->render($this->document), $pdf->filename($this->document))
                ->withMime('application/pdf'),
        ];
    }
}
