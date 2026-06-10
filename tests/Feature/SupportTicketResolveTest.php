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

    public function test_authenticated_user_can_resolve_own_support_ticket(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $ticket = SupportTicket::create([
            'usuario_id' => $user->id,
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
            'usuario_id' => $user->id,
            'situacao' => 'resolved',
        ]);
    }

    public function test_authenticated_user_cannot_resolve_another_users_support_ticket(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        Sanctum::actingAs($otherUser);

        $ticket = SupportTicket::create([
            'usuario_id' => $owner->id,
            'assunto' => 'Chamado de outro usuario',
            'prioridade' => 'high',
            'situacao' => 'open',
        ]);

        $this->patchJson("/api/v1/support/tickets/{$ticket->id}/resolve")
            ->assertNotFound();

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
