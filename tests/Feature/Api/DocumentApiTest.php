<?php

namespace Tests\Feature\Api;

use App\Enums\DocumentType;
use App\Jobs\SendDocumentJob;
use App\Models\Article;
use App\Models\Client;
use App\Models\User;
use Database\Factories\DocumentFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DocumentApiTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $override = []): array
    {
        return array_merge([
            'type' => 'invoice',
            'client_id' => Client::factory()->create()->id,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'lines' => [
                ['description' => 'Audit', 'quantity' => 2, 'unit_price' => 100000, 'tax_rate' => 18],
            ],
        ], $override);
    }

    public function test_create_returns_numbered_draft_with_totals(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/documents', $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.number', 'FAC-'.now()->year.'-0001')
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.total', '236000.00')
            ->assertJsonCount(1, 'data.lines');
    }

    public function test_validation_errors(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/documents', ['type' => 'oops', 'lines' => []])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['type', 'client_id', 'issue_date', 'lines']);

        $this->postJson('/api/v1/documents', $this->payload(['lines' => [['quantity' => 0]]]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['lines.0.quantity', 'lines.0.description']);
    }

    public function test_line_can_reference_an_article(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $article = Article::factory()->create(['unit_price' => 10000, 'tax_rate' => 0]);

        $this->postJson('/api/v1/documents', $this->payload([
            'lines' => [['article_id' => $article->id, 'quantity' => 5]],
        ]))->assertCreated()->assertJsonPath('data.total', '50000.00');
    }

    public function test_full_lifecycle_update_status_convert_delete(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $quote = DocumentFactory::createWithLines();

        $this->putJson("/api/v1/documents/{$quote->id}", ['notes' => 'maj', 'lines' => [
            ['description' => 'Seule ligne', 'quantity' => 1, 'unit_price' => 1000, 'tax_rate' => 0],
        ]])->assertOk()->assertJsonPath('data.total', '1000.00');

        $this->postJson("/api/v1/documents/{$quote->id}/convert-to-invoice")->assertUnprocessable(); // brouillon

        $this->patchJson("/api/v1/documents/{$quote->id}/status", ['status' => 'sent'])
            ->assertOk()->assertJsonPath('data.status', 'sent');

        $this->putJson("/api/v1/documents/{$quote->id}", ['notes' => 'x'])->assertUnprocessable();
        $this->deleteJson("/api/v1/documents/{$quote->id}")->assertUnprocessable();
        $this->patchJson("/api/v1/documents/{$quote->id}/status", ['status' => 'bogus'])->assertUnprocessable();

        $this->postJson("/api/v1/documents/{$quote->id}/convert-to-invoice")
            ->assertCreated()
            ->assertJsonPath('data.type', 'invoice')
            ->assertJsonPath('data.source_quote_id', $quote->id);

        $draft = DocumentFactory::createWithLines(DocumentType::Invoice);
        $this->deleteJson("/api/v1/documents/{$draft->id}")->assertNoContent();
    }

    public function test_filters_and_pagination(): void
    {
        Sanctum::actingAs(User::factory()->create());
        DocumentFactory::createWithLines(DocumentType::Quote);
        DocumentFactory::createWithLines(DocumentType::Invoice);
        DocumentFactory::createWithLines(DocumentType::Invoice);

        $this->getJson('/api/v1/documents?type=invoice')->assertOk()->assertJsonCount(2, 'data');
        $this->getJson('/api/v1/documents?status=paid')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/documents?search=DEV-')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_pdf_download_and_send_job(): void
    {
        Queue::fake();
        Sanctum::actingAs(User::factory()->create());
        $doc = DocumentFactory::createWithLines(client: Client::factory()->create(['email' => 'a@b.sn']));

        $this->get("/api/v1/documents/{$doc->id}/pdf")
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->postJson("/api/v1/documents/{$doc->id}/send")->assertStatus(202);
        Queue::assertPushed(SendDocumentJob::class);
    }

    public function test_send_rejected_when_client_has_no_email(): void
    {
        Queue::fake();
        Sanctum::actingAs(User::factory()->create());
        $doc = DocumentFactory::createWithLines(client: Client::factory()->create(['email' => null]));

        $this->postJson("/api/v1/documents/{$doc->id}/send")->assertUnprocessable();
        Queue::assertNothingPushed();
    }

    public function test_role_permissions_on_documents(): void
    {
        $doc = DocumentFactory::createWithLines();

        // Comptable : lecture + statut, pas de création ni de suppression.
        Sanctum::actingAs(User::factory()->accountant()->create());
        $this->getJson("/api/v1/documents/{$doc->id}")->assertOk();
        $this->postJson('/api/v1/documents', $this->payload())->assertForbidden();
        $this->deleteJson("/api/v1/documents/{$doc->id}")->assertForbidden();
        $this->patchJson("/api/v1/documents/{$doc->id}/status", ['status' => 'sent'])->assertOk();

        // Non authentifié.
        $this->app['auth']->forgetGuards();
        $this->refreshApplication();
        $this->getJson('/api/v1/documents')->assertUnauthorized();
    }
}
