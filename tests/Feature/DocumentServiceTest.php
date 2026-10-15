<?php

namespace Tests\Feature;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Exceptions\BusinessRuleException;
use App\Models\Client;
use App\Models\Document;
use App\Models\User;
use App\Services\DocumentService;
use Database\Factories\DocumentFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentServiceTest extends TestCase
{
    use RefreshDatabase;

    private DocumentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(DocumentService::class);
    }

    public function test_creates_document_with_number_lines_and_totals(): void
    {
        $doc = DocumentFactory::createWithLines();

        $this->assertSame('DEV-'.now()->year.'-0001', $doc->number);
        $this->assertCount(2, $doc->lines);
        $this->assertEquals(250000, $doc->subtotal);
        $this->assertEquals(45000, $doc->tax_total);
        $this->assertEquals(295000, $doc->total);
        $this->assertSame(DocumentStatus::Draft, $doc->status);
    }

    public function test_numbering_is_sequential_per_type(): void
    {
        $a = DocumentFactory::createWithLines();
        $b = DocumentFactory::createWithLines();
        $inv = DocumentFactory::createWithLines(DocumentType::Invoice);

        $this->assertSame('DEV-'.now()->year.'-0001', $a->number);
        $this->assertSame('DEV-'.now()->year.'-0002', $b->number);
        $this->assertSame('FAC-'.now()->year.'-0001', $inv->number);
    }

    public function test_failed_creation_rolls_back_and_does_not_burn_a_number(): void
    {
        try {
            $this->service->create([
                'type' => 'quote',
                'client_id' => Client::factory()->create()->id,
                'issue_date' => now()->toDateString(),
                // quantité manquante => erreur au milieu de la transaction
                'lines' => [['description' => 'X', 'unit_price' => 10]],
            ], User::factory()->create());
            $this->fail('Exception attendue');
        } catch (\Throwable) {
            // attendu
        }

        $this->assertSame(0, Document::count());
        $this->assertDatabaseMissing('number_sequences', ['last_number' => 1]);
        $this->assertSame('DEV-'.now()->year.'-0001', DocumentFactory::createWithLines()->number);
    }

    public function test_only_drafts_can_be_updated_or_deleted(): void
    {
        $doc = DocumentFactory::createWithLines();
        $this->service->changeStatus($doc, DocumentStatus::Sent);

        $this->expectException(BusinessRuleException::class);
        $this->service->update($doc->refresh(), ['notes' => 'tentative']);
    }

    public function test_status_transitions_are_enforced(): void
    {
        $doc = DocumentFactory::createWithLines(DocumentType::Invoice);

        try {
            $this->service->changeStatus($doc, DocumentStatus::Paid); // brouillon → payé interdit
            $this->fail('Exception attendue');
        } catch (BusinessRuleException) {
        }

        $doc = $this->service->changeStatus($doc, DocumentStatus::Sent);
        $this->assertNotNull($doc->sent_at);

        $doc = $this->service->changeStatus($doc, DocumentStatus::Paid);
        $this->assertNotNull($doc->paid_at);

        $this->expectException(BusinessRuleException::class);
        $this->service->changeStatus($doc, DocumentStatus::Sent);
    }

    public function test_quote_converts_once_to_invoice_copying_lines(): void
    {
        $quote = DocumentFactory::createWithLines();

        try {
            $this->service->convertToInvoice($quote); // brouillon refusé
            $this->fail('Exception attendue');
        } catch (BusinessRuleException) {
        }

        $this->service->changeStatus($quote, DocumentStatus::Sent);
        $invoice = $this->service->convertToInvoice($quote->refresh());

        $this->assertSame(DocumentType::Invoice, $invoice->type);
        $this->assertSame($quote->id, $invoice->source_quote_id);
        $this->assertCount(2, $invoice->lines);
        $this->assertEquals($quote->total, $invoice->total);

        $this->expectException(BusinessRuleException::class);
        $this->service->convertToInvoice($quote->refresh());
    }

    public function test_article_defaults_fill_missing_line_fields(): void
    {
        $article = \App\Models\Article::factory()->create(['unit_price' => 10000, 'tax_rate' => 18, 'name' => 'Logo']);

        $doc = DocumentFactory::createWithLines(lines: [['article_id' => $article->id, 'quantity' => 3]]);

        $this->assertSame('Logo', $doc->lines[0]->description);
        $this->assertEquals(35400, $doc->total);
    }
}
