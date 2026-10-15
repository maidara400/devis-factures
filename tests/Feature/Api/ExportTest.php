<?php

namespace Tests\Feature\Api;

use App\Enums\DocumentType;
use App\Jobs\ExportDocumentsCsvJob;
use App\Models\Export;
use App\Models\User;
use App\Services\DocumentCsvExporter;
use Database\Factories\DocumentFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_stream_export_returns_csv_with_filters(): void
    {
        Sanctum::actingAs(User::factory()->accountant()->create());
        $quote = DocumentFactory::createWithLines(DocumentType::Quote);
        DocumentFactory::createWithLines(DocumentType::Invoice);

        $response = $this->get('/api/v1/documents-export?type=quote');

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $content = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBFNuméro;Type;Statut", $content);
        $this->assertStringContainsString($quote->number, $content);
        $this->assertStringNotContainsString('FAC-', $content);
        $this->assertStringContainsString('295000.00', $content);
    }

    public function test_background_export_job_produces_downloadable_file(): void
    {
        Storage::fake('local');
        Queue::fake();
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        DocumentFactory::createWithLines();

        $id = $this->postJson('/api/v1/exports', ['status' => 'draft'])
            ->assertStatus(202)
            ->assertJsonPath('data.status', 'pending')
            ->json('data.id');
        Queue::assertPushed(ExportDocumentsCsvJob::class);

        $this->getJson("/api/v1/exports/{$id}/download")->assertStatus(409); // pas encore prêt

        (new ExportDocumentsCsvJob(Export::find($id)))->handle(app(DocumentCsvExporter::class));

        $this->getJson("/api/v1/exports/{$id}")->assertJsonPath('data.status', 'completed')->assertJsonPath('data.rows_count', 1);
        $this->get("/api/v1/exports/{$id}/download")->assertOk()->assertDownload();
    }

    public function test_exports_are_private_to_their_owner(): void
    {
        $export = Export::create(['user_id' => User::factory()->create()->id]);

        Sanctum::actingAs(User::factory()->create());
        $this->getJson("/api/v1/exports/{$export->id}")->assertForbidden();

        Sanctum::actingAs(User::factory()->admin()->create());
        $this->getJson("/api/v1/exports/{$export->id}")->assertOk();
    }
}
