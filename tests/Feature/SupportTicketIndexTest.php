<?php

namespace Tests\Feature;

use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SupportTicketIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_list_own_support_tickets_with_support_reply(): void
    {
        $user = User::factory()->create([
            'perfil' => User::PROFILE_USER,
        ]);
        $admin = User::factory()->create([
            'perfil' => User::PROFILE_ADMIN,
        ]);
        $otherUser = User::factory()->create([
            'perfil' => User::PROFILE_USER,
        ]);

        $ticket = SupportTicket::create([
            'usuario_id' => $user->id,
            'assunto' => 'Erro no dashboard',
            'categoria' => 'acesso',
            'prioridade' => 'normal',
            'situacao' => 'resolved',
        ]);
        $ticket->messages()->create([
            'usuario_id' => $user->id,
            'mensagem' => 'Nao consigo abrir o dashboard.',
        ]);
        $ticket->messages()->create([
            'usuario_id' => $admin->id,
            'mensagem' => 'Ajustamos seu acesso. Pode tentar novamente.',
        ]);

        SupportTicket::create([
            'usuario_id' => $otherUser->id,
            'assunto' => 'Chamado de outro usuario',
            'prioridade' => 'high',
            'situacao' => 'open',
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/support/tickets')
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.id', $ticket->id)
            ->assertJsonPath('data.data.0.messages_count', 2)
            ->assertJsonPath('data.data.0.messages.0.mensagem', 'Nao consigo abrir o dashboard.')
            ->assertJsonPath('data.data.0.messages.0.user.perfil', User::PROFILE_USER)
            ->assertJsonPath('data.data.0.messages.1.mensagem', 'Ajustamos seu acesso. Pode tentar novamente.')
            ->assertJsonPath('data.data.0.messages.1.user.perfil', User::PROFILE_ADMIN);
    }
}
