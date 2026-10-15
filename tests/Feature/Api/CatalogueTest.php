<?php

namespace Tests\Feature\Api;

use App\Models\Article;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CatalogueTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_crud_clients(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $id = $this->postJson('/api/v1/clients', ['name' => 'ACME SARL', 'email' => 'contact@acme.sn'])
            ->assertCreated()
            ->assertJsonPath('data.country', 'Sénégal')
            ->json('data.id');

        $this->putJson("/api/v1/clients/{$id}", ['name' => 'ACME SA'])->assertOk()->assertJsonPath('data.name', 'ACME SA');
        $this->getJson('/api/v1/clients?search=acme')->assertOk()->assertJsonCount(1, 'data');
        $this->deleteJson("/api/v1/clients/{$id}")->assertNoContent();
        $this->assertSoftDeleted('clients', ['id' => $id]);
    }

    public function test_client_validation(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/clients', ['email' => 'not-an-email'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email']);
    }

    public function test_accountant_can_read_but_not_write(): void
    {
        Sanctum::actingAs(User::factory()->accountant()->create());
        Client::factory()->create();
        Article::factory()->create();

        $this->getJson('/api/v1/clients')->assertOk();
        $this->getJson('/api/v1/articles')->assertOk();
        $this->postJson('/api/v1/clients', ['name' => 'X'])->assertForbidden();
        $this->postJson('/api/v1/articles', [])->assertForbidden();
    }

    public function test_article_reference_must_be_unique_and_price_valid(): void
    {
        Sanctum::actingAs(User::factory()->create());
        Article::factory()->create(['reference' => 'ART-1']);

        $this->postJson('/api/v1/articles', [
            'reference' => 'ART-1', 'name' => 'Doublon', 'unit_price' => -5, 'tax_rate' => 18,
        ])->assertUnprocessable()->assertJsonValidationErrors(['reference', 'unit_price']);

        $this->postJson('/api/v1/articles', [
            'reference' => 'ART-2', 'name' => 'Consulting', 'unit_price' => 50000, 'tax_rate' => 18,
        ])->assertCreated()->assertJsonPath('data.unit', 'unité');
    }
}
