<?php

namespace Tests\Feature;

use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SupportTicketMessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_reply_to_user_support_ticket(): void
    {
        $admin = User::factory()->create([
            'perfil' => User::PROFILE_ADMIN,
        ]);
        $owner = User::factory()->create([
            'perfil' => User::PROFILE_USER,
        ]);
        $ticket = SupportTicket::create([
            'usuario_id' => $owner->id,
            'assunto' => 'Erro no dashboard',
            'prioridade' => 'normal',
            'situacao' => 'open',
        ]);

        Sanctum::actingAs($admin);

        $this->postJson("/api/v1/support/tickets/{$ticket->id}/messages", [
            'mensagem' => 'Ajustamos seu acesso. Pode tentar novamente.',
        ])
            ->assertCreated()
            ->assertJsonPath('data.mensagem', 'Ajustamos seu acesso. Pode tentar novamente.')
            ->assertJsonPath('data.user.perfil', User::PROFILE_ADMIN);

        $this->assertDatabaseHas('mensagens_chamados_suporte', [
            'chamado_id' => $ticket->id,
            'usuario_id' => $admin->id,
            'mensagem' => 'Ajustamos seu acesso. Pode tentar novamente.',
        ]);
    }

    public function test_non_owner_non_admin_cannot_reply_to_support_ticket(): void
    {
        $owner = User::factory()->create([
            'perfil' => User::PROFILE_USER,
        ]);
        $otherUser = User::factory()->create([
            'perfil' => User::PROFILE_USER,
        ]);
        $ticket = SupportTicket::create([
            'usuario_id' => $owner->id,
            'assunto' => 'Erro no dashboard',
            'prioridade' => 'normal',
            'situacao' => 'open',
        ]);

        Sanctum::actingAs($otherUser);

        $this->postJson("/api/v1/support/tickets/{$ticket->id}/messages", [
            'mensagem' => 'Tentativa indevida.',
        ])->assertNotFound();
    }
}
