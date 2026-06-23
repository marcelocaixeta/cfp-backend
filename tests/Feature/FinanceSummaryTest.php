<?php

namespace Tests\Feature;

use App\Models\CreditCardDebt;
use App\Models\HomeBill;
use App\Models\Loan;
use App\Models\MonthlyIncome;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FinanceSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_get_monthly_spending_control_without_discounting_paid_items(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        Sanctum::actingAs($user);

        MonthlyIncome::create([
            'usuario_id' => $user->id,
            'descricao' => 'Salario liquido',
            'valor' => '6500.00',
            'data_recebimento' => '2026-06-05',
            'tipo_receita' => 'salary',
        ]);

        MonthlyIncome::create([
            'usuario_id' => $user->id,
            'descricao' => 'Freelance',
            'valor' => '900.00',
            'data_recebimento' => '2026-06-12',
            'tipo_receita' => 'freelance',
        ]);

        MonthlyIncome::create([
            'usuario_id' => $user->id,
            'descricao' => 'Salario fora do mes',
            'valor' => '7000.00',
            'data_recebimento' => '2026-07-05',
            'tipo_receita' => 'salary',
        ]);

        CreditCardDebt::create([
            'usuario_id' => $user->id,
            'descricao' => 'Cartao pendente',
            'valor_total' => '300.00',
            'valor_parcela' => '100.00',
            'primeira_data_vencimento' => '2026-06-10',
            'situacao' => 'pending',
        ]);

        CreditCardDebt::create([
            'usuario_id' => $user->id,
            'descricao' => 'Cartao pago',
            'valor_total' => '600.00',
            'valor_parcela' => '200.00',
            'primeira_data_vencimento' => '2026-06-11',
            'situacao' => 'paid',
        ]);

        CreditCardDebt::create([
            'usuario_id' => $user->id,
            'descricao' => 'Cartao cancelado',
            'valor_total' => '999.00',
            'valor_parcela' => '999.00',
            'primeira_data_vencimento' => '2026-06-12',
            'situacao' => 'canceled',
        ]);

        CreditCardDebt::create([
            'usuario_id' => $otherUser->id,
            'descricao' => 'Cartao de outro usuario',
            'valor_total' => '500.00',
            'valor_parcela' => '500.00',
            'primeira_data_vencimento' => '2026-06-10',
            'situacao' => 'pending',
        ]);

        $loan = Loan::create([
            'usuario_id' => $user->id,
            'credor_nome' => 'Banco CFP',
            'valor_principal' => '2000.00',
            'quantidade_parcelas' => 4,
            'valor_parcela' => '500.00',
            'primeira_data_vencimento' => '2026-06-15',
            'situacao' => 'active',
        ]);

        $loan->installments()->createMany([
            [
                'usuario_id' => $user->id,
                'numero_parcela' => 1,
                'data_vencimento' => '2026-06-15',
                'valor' => '400.00',
                'situacao' => 'pending',
            ],
            [
                'usuario_id' => $user->id,
                'numero_parcela' => 2,
                'data_vencimento' => '2026-06-20',
                'valor' => '500.00',
                'situacao' => 'paid',
            ],
            [
                'usuario_id' => $user->id,
                'numero_parcela' => 3,
                'data_vencimento' => '2026-06-25',
                'valor' => '700.00',
                'situacao' => 'canceled',
            ],
            [
                'usuario_id' => $user->id,
                'numero_parcela' => 4,
                'data_vencimento' => '2026-07-15',
                'valor' => '600.00',
                'situacao' => 'pending',
            ],
        ]);

        HomeBill::create([
            'usuario_id' => $user->id,
            'tipo' => 'luz',
            'descricao' => 'Energia',
            'valor' => '150.00',
            'data_vencimento' => '2026-06-18',
            'situacao' => 'overdue',
        ]);

        HomeBill::create([
            'usuario_id' => $user->id,
            'tipo' => 'agua',
            'descricao' => 'Agua paga',
            'valor' => '50.00',
            'data_vencimento' => '2026-06-22',
            'situacao' => 'paid',
        ]);

        HomeBill::create([
            'usuario_id' => $user->id,
            'tipo' => 'telefone',
            'descricao' => 'Telefone cancelado',
            'valor' => '123.00',
            'data_vencimento' => '2026-06-23',
            'situacao' => 'canceled',
        ]);

        $this->getJson('/api/v1/finance/summary?mes=2026-06')
            ->assertOk()
            ->assertJsonPath('data.controle_gastos_mensais.periodo.mes', '2026-06')
            ->assertJsonPath('data.controle_gastos_mensais.salario_liquido', '6500.00')
            ->assertJsonPath('data.controle_gastos_mensais.total_gastos', '1400.00')
            ->assertJsonPath('data.controle_gastos_mensais.saldo_previsto', '5100.00')
            ->assertJsonPath('data.controle_gastos_mensais.gastando_mais_do_que_ganha', false)
            ->assertJsonPath('data.controle_gastos_mensais.composicao_gastos.0.valor', '300.00')
            ->assertJsonPath('data.controle_gastos_mensais.composicao_gastos.1.valor', '900.00')
            ->assertJsonPath('data.controle_gastos_mensais.composicao_gastos.2.valor', '200.00')
            ->assertJsonPath('data.controle_gastos_mensais.grafico.tipo', 'bar')
            ->assertJsonPath('data.controle_gastos_mensais.grafico.itens.0.valor', '6500.00')
            ->assertJsonPath('data.controle_gastos_mensais.grafico.itens.1.valor', '1400.00');
    }
}
