<?php

namespace Tests\Feature;

use App\Models\CreditCard;
use App\Models\CreditCardDebt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CreditCardDebtTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_see_credit_card_on_credit_card_debt_installment(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $creditCard = CreditCard::create([
            'usuario_id' => $user->id,
            'nome' => 'Nubank Roxinho',
            'ultimos_quatro_digitos' => '4321',
            'bandeira' => 'mastercard',
        ]);

        CreditCardDebt::create([
            'usuario_id' => $user->id,
            'cartao_credito_id' => $creditCard->id,
            'descricao' => 'Notebook',
            'valor_total' => '2400.00',
            'quantidade_parcelas' => 12,
            'parcela_atual' => 3,
            'valor_parcela' => '200.00',
            'primeira_data_vencimento' => '2026-06-20',
            'situacao' => 'pending',
        ]);

        $this->getJson('/api/v1/finance/credit-card-debts')
            ->assertOk()
            ->assertJsonPath('data.data.0.descricao', 'Notebook')
            ->assertJsonPath('data.data.0.parcela_atual', 3)
            ->assertJsonPath('data.data.0.cartao_credito.id', $creditCard->id)
            ->assertJsonPath('data.data.0.cartao_credito.nome', 'Nubank Roxinho')
            ->assertJsonPath('data.data.0.cartao_credito.ultimos_quatro_digitos', '4321')
            ->assertJsonPath('data.data.0.cartao_credito.bandeira', 'mastercard');
    }
}
