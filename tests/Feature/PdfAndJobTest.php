<?php

namespace Tests\Feature;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Jobs\SendDocumentJob;
use App\Mail\DocumentMail;
use App\Models\Client;
use App\Services\DocumentPdfService;
use Database\Factories\DocumentFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PdfAndJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_pdf_is_rendered_for_quotes_and_invoices(): void
    {
        foreach ([DocumentType::Quote, DocumentType::Invoice] as $type) {
            $pdf = app(DocumentPdfService::class)->render(DocumentFactory::createWithLines($type));

            $this->assertStringStartsWith('%PDF', $pdf);
            $this->assertGreaterThan(1000, strlen($pdf));
        }
    }

    public function test_send_job_emails_the_client_and_marks_document_as_sent(): void
    {
        Mail::fake();
        $client = Client::factory()->create(['email' => 'client@example.com']);
        $document = DocumentFactory::createWithLines(client: $client);

        (new SendDocumentJob($document))->handle(app(\App\Services\DocumentService::class));

        Mail::assertSent(DocumentMail::class, fn ($mail) => $mail->hasTo('client@example.com'));
        $this->assertSame(DocumentStatus::Sent, $document->refresh()->status);
    }

    public function test_send_job_fails_when_client_has_no_email(): void
    {
        Mail::fake();
        $document = DocumentFactory::createWithLines(client: Client::factory()->create(['email' => null]));

        $job = new SendDocumentJob($document);
        $job->withFakeQueueInteractions();
        $job->handle(app(\App\Services\DocumentService::class));

        Mail::assertNothingSent();
        $job->assertFailed();
        $this->assertSame(DocumentStatus::Draft, $document->refresh()->status);
    }

    public function test_mail_renders_and_carries_the_pdf_attachment(): void
    {
        $document = DocumentFactory::createWithLines();
        $mail = new DocumentMail($document->load('client'));

        $mail->assertSeeInHtml($document->number);
        $this->assertCount(1, $mail->attachments());
    }
}
