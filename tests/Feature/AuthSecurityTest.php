<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_registration_endpoint_is_not_exposed(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'New User',
            'email' => 'new@example.test',
            'password' => 'StrongPassword!2026',
            'password_confirmation' => 'StrongPassword!2026',
            'role' => 'administrateur',
        ])->assertNotFound();
    }

    public function test_non_admin_cannot_create_an_account(): void
    {
        $user = User::factory()->create(['role' => 'gestionnaire']);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/auth/register-utilisateur', [
                'name' => 'New User',
                'email' => 'new@example.test',
                'password' => 'StrongPassword!2026',
                'password_confirmation' => 'StrongPassword!2026',
                'role' => 'consultation',
            ])
            ->assertForbidden();
    }

    public function test_admin_can_create_a_user_with_a_hashed_password(): void
    {
        $admin = User::factory()->create(['role' => 'administrateur']);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/auth/register-utilisateur', [
                'name' => 'New User',
                'email' => 'new@example.test',
                'password' => 'StrongPassword!2026',
                'password_confirmation' => 'StrongPassword!2026',
                'role' => 'consultation',
            ])
            ->assertCreated()
            ->assertJsonPath('data.user.email', 'new@example.test')
            ->assertJsonMissingPath('data.user.password');

        $user = User::where('email', 'new@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('StrongPassword!2026', $user->password));
    }
}
