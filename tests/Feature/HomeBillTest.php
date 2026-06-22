<?php

namespace Tests\Feature;

use App\Models\HomeBill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class HomeBillTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_home_bill(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/finance/home-bills', [
            'tipo' => 'agua',
            'descricao' => 'Conta de agua de junho',
            'fornecedor_nome' => 'Saneamento CFP',
            'valor' => '98.75',
            'data_vencimento' => '2026-06-20',
            'mes_referencia' => '2026-06-01',
            'observacoes' => 'Casa',
        ])
            ->assertCreated()
            ->assertJsonPath('data.tipo', 'agua')
            ->assertJsonPath('data.descricao', 'Conta de agua de junho')
            ->assertJsonPath('data.valor', '98.75')
            ->assertJsonPath('data.situacao', 'pending');

        $this->assertDatabaseHas('contas_casa', [
            'usuario_id' => $user->id,
            'tipo' => 'agua',
            'descricao' => 'Conta de agua de junho',
            'valor' => '98.75',
            'situacao' => 'pending',
        ]);
    }

    public function test_home_bill_type_must_be_water_electricity_or_phone(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/finance/home-bills', [
            'tipo' => 'internet',
            'descricao' => 'Internet',
            'valor' => '120.00',
            'data_vencimento' => '2026-06-20',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('tipo');
    }

    public function test_authenticated_user_lists_only_own_home_bills_and_can_filter_by_type(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        Sanctum::actingAs($user);

        HomeBill::create([
            'usuario_id' => $user->id,
            'tipo' => 'luz',
            'descricao' => 'Conta de luz',
            'valor' => '210.40',
            'data_vencimento' => '2026-06-18',
        ]);

        HomeBill::create([
            'usuario_id' => $user->id,
            'tipo' => 'telefone',
            'descricao' => 'Telefone movel',
            'valor' => '59.90',
            'data_vencimento' => '2026-06-22',
        ]);

        HomeBill::create([
            'usuario_id' => $otherUser->id,
            'tipo' => 'luz',
            'descricao' => 'Conta de outro usuario',
            'valor' => '100.00',
            'data_vencimento' => '2026-06-18',
        ]);

        $this->getJson('/api/v1/finance/home-bills?tipo=luz')
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.tipo', 'luz')
            ->assertJsonPath('data.data.0.descricao', 'Conta de luz');
    }

    public function test_user_cannot_see_home_bill_from_another_user(): void
    {
        $owner = User::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $homeBill = HomeBill::create([
            'usuario_id' => $owner->id,
            'tipo' => 'telefone',
            'descricao' => 'Telefone fixo',
            'valor' => '80.00',
            'data_vencimento' => '2026-06-25',
        ]);

        $this->getJson("/api/v1/finance/home-bills/{$homeBill->id}")
            ->assertNotFound();
    }

    public function test_authenticated_user_can_update_home_bill(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $homeBill = HomeBill::create([
            'usuario_id' => $user->id,
            'tipo' => 'agua',
            'descricao' => 'Conta de agua',
            'fornecedor_nome' => 'Fornecedor antigo',
            'valor' => '98.75',
            'data_vencimento' => '2026-06-20',
        ]);

        $this->patchJson("/api/v1/finance/home-bills/{$homeBill->id}", [
            'tipo' => 'luz',
            'descricao' => 'Conta de luz atualizada',
            'fornecedor_nome' => 'Energia CFP',
            'valor' => '185.30',
            'data_vencimento' => '2026-06-25',
            'situacao' => 'paid',
        ])
            ->assertOk()
            ->assertJsonPath('data.tipo', 'luz')
            ->assertJsonPath('data.descricao', 'Conta de luz atualizada')
            ->assertJsonPath('data.fornecedor_nome', 'Energia CFP')
            ->assertJsonPath('data.valor', '185.30')
            ->assertJsonPath('data.situacao', 'paid');

        $this->assertDatabaseHas('contas_casa', [
            'id' => $homeBill->id,
            'usuario_id' => $user->id,
            'tipo' => 'luz',
            'descricao' => 'Conta de luz atualizada',
            'fornecedor_nome' => 'Energia CFP',
            'valor' => '185.30',
            'situacao' => 'paid',
        ]);
    }

    public function test_authenticated_user_can_delete_home_bill(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $homeBill = HomeBill::create([
            'usuario_id' => $user->id,
            'tipo' => 'telefone',
            'descricao' => 'Telefone fixo',
            'valor' => '80.00',
            'data_vencimento' => '2026-06-25',
        ]);

        $this->deleteJson("/api/v1/finance/home-bills/{$homeBill->id}")
            ->assertNoContent();

        $this->assertSoftDeleted('contas_casa', [
            'id' => $homeBill->id,
            'usuario_id' => $user->id,
        ], deletedAtColumn: 'excluido_em');
    }

    public function test_user_cannot_update_or_delete_home_bill_from_another_user(): void
    {
        $owner = User::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $homeBill = HomeBill::create([
            'usuario_id' => $owner->id,
            'tipo' => 'agua',
            'descricao' => 'Conta protegida',
            'valor' => '120.00',
            'data_vencimento' => '2026-06-25',
        ]);

        $this->patchJson("/api/v1/finance/home-bills/{$homeBill->id}", [
            'descricao' => 'Tentativa de edicao',
        ])
            ->assertNotFound();

        $this->deleteJson("/api/v1/finance/home-bills/{$homeBill->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('contas_casa', [
            'id' => $homeBill->id,
            'descricao' => 'Conta protegida',
            'excluido_em' => null,
        ]);
    }
}
