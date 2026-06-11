<?php

namespace Tests\Feature;

use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SupportTicketResolveTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_resolve_support_ticket(): void
    {
        $admin = User::factory()->create([
            'perfil' => User::PROFILE_ADMIN,
        ]);
        $owner = User::factory()->create([
            'perfil' => User::PROFILE_USER,
        ]);
        Sanctum::actingAs($admin);

        $ticket = SupportTicket::create([
            'usuario_id' => $owner->id,
            'assunto' => 'Erro ao acessar dashboard',
            'categoria' => 'acesso',
            'prioridade' => 'normal',
            'situacao' => 'open',
        ]);

        $this->patchJson("/api/v1/support/tickets/{$ticket->id}/resolve")
            ->assertOk()
            ->assertJsonPath('data.id', $ticket->id)
            ->assertJsonPath('data.situacao', 'resolved');

        $this->assertDatabaseHas('chamados_suporte', [
            'id' => $ticket->id,
            'usuario_id' => $owner->id,
            'situacao' => 'resolved',
        ]);
    }

    public function test_non_admin_cannot_resolve_own_support_ticket(): void
    {
        $user = User::factory()->create([
            'perfil' => User::PROFILE_USER,
        ]);
        Sanctum::actingAs($user);

        $ticket = SupportTicket::create([
            'usuario_id' => $user->id,
            'assunto' => 'Chamado do usuario comum',
            'prioridade' => 'high',
            'situacao' => 'open',
        ]);

        $this->patchJson("/api/v1/support/tickets/{$ticket->id}/resolve")
            ->assertForbidden();

        $this->assertDatabaseHas('chamados_suporte', [
            'id' => $ticket->id,
            'situacao' => 'open',
        ]);
    }

    public function test_guest_cannot_resolve_support_ticket(): void
    {
        $ticket = SupportTicket::create([
            'usuario_id' => User::factory()->create()->id,
            'assunto' => 'Chamado sem autenticacao',
            'prioridade' => 'normal',
            'situacao' => 'open',
        ]);

        $this->patchJson("/api/v1/support/tickets/{$ticket->id}/resolve")
            ->assertUnauthorized();
    }
}
