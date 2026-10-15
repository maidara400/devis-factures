<?php

namespace Tests\Feature\Web;

use App\Enums\DocumentType;
use App\Models\Article;
use App\Models\Client;
use App\Models\Document;
use App\Models\User;
use Database\Factories\DocumentFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebInterfaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/documents')->assertRedirect('/login');
        $this->get('/login')->assertOk()->assertSee('Se connecter');
    }

    public function test_login_logout_flow(): void
    {
        $user = User::factory()->create(['password' => 'Secret123']);

        $this->post('/login', ['email' => $user->email, 'password' => 'bad'])->assertSessionHasErrors('email');
        $this->post('/login', ['email' => $user->email, 'password' => 'Secret123'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);

        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        $user = User::factory()->inactive()->create(['password' => 'Secret123']);

        $this->post('/login', ['email' => $user->email, 'password' => 'Secret123'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_every_page_renders_for_a_manager(): void
    {
        $this->actingAs(User::factory()->create());
        $client = Client::factory()->create();
        Article::factory()->create();
        $quote = DocumentFactory::createWithLines(client: $client);
        $invoice = DocumentFactory::createWithLines(DocumentType::Invoice, client: $client);

        foreach ([
            '/dashboard', '/clients', '/clients/create', "/clients/{$client->id}", "/clients/{$client->id}/edit",
            '/articles', '/articles/create', '/documents', '/documents?type=invoice&status=draft&search=FAC',
            '/documents/create?type=invoice', "/documents/{$quote->id}", "/documents/{$quote->id}/edit",
            "/documents/{$invoice->id}",
        ] as $url) {
            $this->get($url)->assertOk();
        }

        $this->get("/documents/{$quote->id}/pdf")->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->get('/users')->assertForbidden();
    }

    public function test_admin_sees_user_management(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $this->get('/users')->assertOk()->assertSee($admin->email);
        $this->get('/users/create')->assertOk();
        $this->get("/users/{$admin->id}/edit")->assertOk();
    }

    public function test_creating_a_document_through_the_form(): void
    {
        $this->actingAs(User::factory()->create());
        $client = Client::factory()->create();

        $response = $this->post('/documents', [
            'type' => 'quote',
            'client_id' => $client->id,
            'issue_date' => now()->toDateString(),
            'lines' => [['article_id' => '', 'description' => 'Prestation', 'quantity' => 2, 'unit_price' => 5000, 'tax_rate' => 18]],
        ]);

        $document = Document::firstOrFail();
        $response->assertRedirect("/documents/{$document->id}");
        $this->assertEquals(11800, $document->total);
    }

    public function test_status_workflow_and_business_errors_through_the_ui(): void
    {
        $this->actingAs(User::factory()->create());
        $doc = DocumentFactory::createWithLines(DocumentType::Invoice);

        $this->patch("/documents/{$doc->id}/status", ['status' => 'paid'])->assertSessionHas('error'); // draft -> paid refusé
        $this->patch("/documents/{$doc->id}/status", ['status' => 'sent'])->assertSessionHas('success');
        $this->get("/documents/{$doc->id}/edit")->assertRedirect("/documents/{$doc->id}");
        $this->delete("/documents/{$doc->id}")->assertSessionHas('error');
    }

    public function test_accountant_cannot_create_documents_or_manage_catalogue(): void
    {
        $this->actingAs(User::factory()->accountant()->create());

        $this->get('/documents/create')->assertForbidden();
        $this->get('/clients/create')->assertForbidden();
        $this->get('/articles/create')->assertForbidden();
        $this->get('/documents')->assertOk();
    }

    public function test_csv_export_from_the_ui(): void
    {
        $this->actingAs(User::factory()->accountant()->create());
        $doc = DocumentFactory::createWithLines();

        $this->get('/documents-export')->assertOk()->assertDownload();
        $this->assertStringContainsString($doc->number, $this->get('/documents-export')->streamedContent());
    }
}
