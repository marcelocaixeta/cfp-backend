<?php

namespace Tests\Feature;

use App\Models\CreditCard;
use App\Models\CreditCardDebt;
use App\Models\Loan;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CurrentWeekDueDateTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_authenticated_user_can_list_current_week_debt_and_loan_due_dates(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-10 10:00:00', config('app.timezone')));

        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        Sanctum::actingAs($user);

        $creditCard = CreditCard::create([
            'usuario_id' => $user->id,
            'nome' => 'Cartao principal',
            'ultimos_quatro_digitos' => '1234',
        ]);

        CreditCardDebt::create([
            'usuario_id' => $user->id,
            'cartao_credito_id' => $creditCard->id,
            'descricao' => 'Fatura do cartao',
            'valor_total' => '300.00',
            'quantidade_parcelas' => 3,
            'parcela_atual' => 1,
            'valor_parcela' => '100.00',
            'primeira_data_vencimento' => '2026-06-12',
            'situacao' => 'pending',
        ]);

        CreditCardDebt::create([
            'usuario_id' => $user->id,
            'descricao' => 'Divida paga',
            'valor_total' => '90.00',
            'valor_parcela' => '90.00',
            'primeira_data_vencimento' => '2026-06-11',
            'situacao' => 'paid',
        ]);

        CreditCardDebt::create([
            'usuario_id' => $otherUser->id,
            'descricao' => 'Divida de outro usuario',
            'valor_total' => '70.00',
            'valor_parcela' => '70.00',
            'primeira_data_vencimento' => '2026-06-12',
            'situacao' => 'pending',
        ]);

        $loan = Loan::create([
            'usuario_id' => $user->id,
            'credor_nome' => 'Banco CFP',
            'descricao' => 'Emprestimo pessoal',
            'valor_principal' => '500.00',
            'quantidade_parcelas' => 5,
            'valor_parcela' => '100.00',
            'primeira_data_vencimento' => '2026-06-10',
            'situacao' => 'active',
        ]);

        $loan->installments()->createMany([
            [
                'usuario_id' => $user->id,
                'numero_parcela' => 1,
                'data_vencimento' => '2026-06-10',
                'valor' => '100.00',
                'situacao' => 'pending',
            ],
            [
                'usuario_id' => $user->id,
                'numero_parcela' => 2,
                'data_vencimento' => '2026-06-18',
                'valor' => '100.00',
                'situacao' => 'pending',
            ],
            [
                'usuario_id' => $user->id,
                'numero_parcela' => 3,
                'data_vencimento' => '2026-06-13',
                'valor' => '100.00',
                'situacao' => 'canceled',
            ],
        ]);

        $this->getJson('/api/v1/finance/current-week-due-dates')
            ->assertOk()
            ->assertJsonPath('data.periodo.inicio', '2026-06-08')
            ->assertJsonPath('data.periodo.fim', '2026-06-14')
            ->assertJsonCount(1, 'data.dividas_cartao_credito')
            ->assertJsonCount(1, 'data.parcelas_emprestimos')
            ->assertJsonPath('data.dividas_cartao_credito.0.descricao', 'Fatura do cartao')
            ->assertJsonPath('data.dividas_cartao_credito.0.data_vencimento', '2026-06-12')
            ->assertJsonPath('data.dividas_cartao_credito.0.cartao_credito.nome', 'Cartao principal')
            ->assertJsonPath('data.parcelas_emprestimos.0.credor_nome', 'Banco CFP')
            ->assertJsonPath('data.parcelas_emprestimos.0.data_vencimento', '2026-06-10')
            ->assertJsonPath('data.totais.dividas_cartao_credito.count', 1)
            ->assertJsonPath('data.totais.parcelas_emprestimos.count', 1);
    }

    public function test_guest_cannot_list_current_week_due_dates(): void
    {
        $this->getJson('/api/v1/finance/current-week-due-dates')
            ->assertUnauthorized();
    }
}
