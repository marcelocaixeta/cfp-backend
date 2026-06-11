<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_users_for_select_input(): void
    {
        $admin = User::factory()->create([
            'nome' => 'Admin Marcelo',
            'perfil' => User::PROFILE_ADMIN,
        ]);
        $user = User::factory()->create([
            'nome' => 'Usuario Ana',
            'perfil' => User::PROFILE_USER,
        ]);
        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/users')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $admin->id)
            ->assertJsonPath('data.0.nome', 'Admin Marcelo')
            ->assertJsonPath('data.0.perfil', User::PROFILE_ADMIN)
            ->assertJsonPath('data.0.label', 'Admin Marcelo (admin)')
            ->assertJsonPath('data.1.id', $user->id)
            ->assertJsonPath('data.1.nome', 'Usuario Ana')
            ->assertJsonPath('data.1.perfil', User::PROFILE_USER)
            ->assertJsonPath('data.1.label', 'Usuario Ana (usuario)');
    }

    public function test_non_admin_cannot_list_users(): void
    {
        Sanctum::actingAs(User::factory()->create([
            'perfil' => User::PROFILE_USER,
        ]));

        $this->getJson('/api/v1/users')
            ->assertForbidden();
    }

    public function test_guest_cannot_list_users(): void
    {
        $this->getJson('/api/v1/users')
            ->assertUnauthorized();
    }

    public function test_admin_can_update_user_profile(): void
    {
        $admin = User::factory()->create([
            'perfil' => User::PROFILE_ADMIN,
        ]);
        $user = User::factory()->create([
            'perfil' => User::PROFILE_USER,
        ]);
        Sanctum::actingAs($admin);

        $this->patchJson("/api/v1/users/{$user->id}/profile", [
            'perfil' => User::PROFILE_ADMIN,
        ])
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.perfil', User::PROFILE_ADMIN);

        $this->assertDatabaseHas('usuarios', [
            'id' => $user->id,
            'perfil' => User::PROFILE_ADMIN,
        ]);
    }

    public function test_non_admin_cannot_update_user_profile(): void
    {
        $actingUser = User::factory()->create([
            'perfil' => User::PROFILE_USER,
        ]);
        $user = User::factory()->create([
            'perfil' => User::PROFILE_USER,
        ]);
        Sanctum::actingAs($actingUser);

        $this->patchJson("/api/v1/users/{$user->id}/profile", [
            'perfil' => User::PROFILE_ADMIN,
        ])
            ->assertForbidden();

        $this->assertDatabaseHas('usuarios', [
            'id' => $user->id,
            'perfil' => User::PROFILE_USER,
        ]);
    }

    public function test_profile_must_be_valid(): void
    {
        $admin = User::factory()->create([
            'perfil' => User::PROFILE_ADMIN,
        ]);
        $user = User::factory()->create();
        Sanctum::actingAs($admin);

        $this->patchJson("/api/v1/users/{$user->id}/profile", [
            'perfil' => 'gerente',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('perfil');
    }

    public function test_guest_cannot_update_user_profile(): void
    {
        $user = User::factory()->create();

        $this->patchJson("/api/v1/users/{$user->id}/profile", [
            'perfil' => User::PROFILE_ADMIN,
        ])
            ->assertUnauthorized();
    }
}
