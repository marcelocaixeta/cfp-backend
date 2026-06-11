<?php

namespace Tests\Feature;

use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SupportTicketAdminIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_all_support_tickets(): void
    {
        $admin = User::factory()->create([
            'perfil' => User::PROFILE_ADMIN,
        ]);
        $firstUser = User::factory()->create([
            'nome' => 'Usuario Ana',
            'perfil' => User::PROFILE_USER,
        ]);
        $secondUser = User::factory()->create([
            'nome' => 'Usuario Bruno',
            'perfil' => User::PROFILE_USER,
        ]);
        Sanctum::actingAs($admin);

        $firstTicket = SupportTicket::create([
            'usuario_id' => $firstUser->id,
            'assunto' => 'Erro ao acessar dashboard',
            'categoria' => 'acesso',
            'prioridade' => 'normal',
            'situacao' => 'open',
        ]);
        $firstTicket->messages()->create([
            'usuario_id' => $firstUser->id,
            'mensagem' => 'Nao consigo acessar.',
        ]);

        $secondTicket = SupportTicket::create([
            'usuario_id' => $secondUser->id,
            'assunto' => 'Duvida sobre boleto',
            'categoria' => 'financeiro',
            'prioridade' => 'high',
            'situacao' => 'waiting_support',
        ]);

        $this->getJson('/api/v1/support/tickets/all')
            ->assertOk()
            ->assertJsonCount(2, 'data.data')
            ->assertJsonPath('data.data.0.id', $secondTicket->id)
            ->assertJsonPath('data.data.0.user.nome', 'Usuario Bruno')
            ->assertJsonPath('data.data.0.messages_count', 0)
            ->assertJsonPath('data.data.1.id', $firstTicket->id)
            ->assertJsonPath('data.data.1.user.nome', 'Usuario Ana')
            ->assertJsonPath('data.data.1.messages_count', 1);
    }

    public function test_non_admin_cannot_list_all_support_tickets(): void
    {
        Sanctum::actingAs(User::factory()->create([
            'perfil' => User::PROFILE_USER,
        ]));

        $this->getJson('/api/v1/support/tickets/all')
            ->assertForbidden();
    }

    public function test_guest_cannot_list_all_support_tickets(): void
    {
        $this->getJson('/api/v1/support/tickets/all')
            ->assertUnauthorized();
    }
}
