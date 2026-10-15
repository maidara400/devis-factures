<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_a_token(): void
    {
        $user = User::factory()->create(['password' => 'Secret123']);

        $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'Secret123'])
            ->assertOk()
            ->assertJsonStructure(['token', 'token_type', 'user' => ['id', 'email', 'role', 'permissions']]);
    }

    public function test_login_rejects_wrong_password_and_inactive_accounts(): void
    {
        $user = User::factory()->create(['password' => 'Secret123']);
        $inactive = User::factory()->inactive()->create(['password' => 'Secret123']);

        $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'nope'])
            ->assertUnprocessable();
        $this->postJson('/api/v1/auth/login', ['email' => $inactive->email, 'password' => 'Secret123'])
            ->assertUnprocessable();
    }

    public function test_protected_routes_require_authentication(): void
    {
        $this->getJson('/api/v1/auth/me')->assertUnauthorized();
    }

    public function test_only_admin_can_manage_users(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $this->getJson('/api/v1/users')->assertForbidden();

        Sanctum::actingAs(User::factory()->admin()->create());
        $this->getJson('/api/v1/users')->assertOk();
    }

    public function test_admin_creates_user_with_role_and_cannot_demote_self(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/users', [
            'name' => 'Nouveau', 'email' => 'new@example.com',
            'password' => 'Password123', 'role' => 'accountant',
        ])->assertCreated()->assertJsonPath('data.role', 'accountant');

        $this->putJson("/api/v1/users/{$admin->id}", ['role' => 'accountant'])->assertUnprocessable();
        $this->deleteJson("/api/v1/users/{$admin->id}")->assertUnprocessable();
    }

    public function test_logout_revokes_token(): void
    {
        $user = User::factory()->create(['password' => 'Secret123']);
        $token = $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'Secret123'])
            ->json('token');

        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertOk();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
