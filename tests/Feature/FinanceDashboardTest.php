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

class FinanceDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_get_monthly_dashboard_totals_and_chart_data(): void
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
            'descricao' => 'Receita fora do mes',
            'valor' => '999.00',
            'data_recebimento' => '2026-07-05',
            'tipo_receita' => 'salary',
        ]);

        CreditCardDebt::create([
            'usuario_id' => $user->id,
            'descricao' => 'Gastos do cartao',
            'valor_total' => '3000.00',
            'quantidade_parcelas' => 2,
            'parcela_atual' => 1,
            'valor_parcela' => '1500.00',
            'primeira_data_vencimento' => '2026-06-12',
            'situacao' => 'pending',
        ]);

        CreditCardDebt::create([
            'usuario_id' => $user->id,
            'descricao' => 'Cartao pago',
            'valor_total' => '80.00',
            'valor_parcela' => '80.00',
            'primeira_data_vencimento' => '2026-06-12',
            'situacao' => 'paid',
        ]);

        $loan = Loan::create([
            'usuario_id' => $user->id,
            'credor_nome' => 'Banco CFP',
            'descricao' => 'Emprestimo pessoal',
            'valor_principal' => '3000.00',
            'quantidade_parcelas' => 3,
            'valor_parcela' => '1430.52',
            'primeira_data_vencimento' => '2026-06-16',
        ]);

        $loan->installments()->create([
            'usuario_id' => $user->id,
            'numero_parcela' => 1,
            'data_vencimento' => '2026-06-16',
            'valor' => '1430.52',
            'situacao' => 'pending',
        ]);

        $loan->installments()->create([
            'usuario_id' => $user->id,
            'numero_parcela' => 2,
            'data_vencimento' => '2026-07-16',
            'valor' => '1430.52',
            'situacao' => 'pending',
        ]);

        HomeBill::create([
            'usuario_id' => $user->id,
            'tipo' => 'luz',
            'descricao' => 'Contas da casa',
            'valor' => '2346.19',
            'data_vencimento' => '2026-06-20',
            'situacao' => 'overdue',
        ]);

        HomeBill::create([
            'usuario_id' => $otherUser->id,
            'tipo' => 'luz',
            'descricao' => 'Conta de outro usuario',
            'valor' => '1000.00',
            'data_vencimento' => '2026-06-20',
            'situacao' => 'pending',
        ]);

        $this->getJson('/api/v1/finance/dashboard?mes=2026-06')
            ->assertOk()
            ->assertJsonPath('data.periodo.mes', '2026-06')
            ->assertJsonPath('data.totais.total_a_pagar', '5276.71')
            ->assertJsonPath('data.totais.salario_liquido', '6500.00')
            ->assertJsonPath('data.totais.total_receitas', '6500.00')
            ->assertJsonPath('data.totais.saldo', '1223.29')
            ->assertJsonPath('data.composicao_pagamentos.0.valor', '1500.00')
            ->assertJsonPath('data.composicao_pagamentos.1.valor', '1430.52')
            ->assertJsonPath('data.composicao_pagamentos.2.valor', '2346.19')
            ->assertJsonPath('data.grafico.tipo', 'pie')
            ->assertJsonPath('data.grafico.itens.0.chave', 'saldo')
            ->assertJsonPath('data.grafico.itens.0.valor', '1223.29');
    }

    public function test_guest_cannot_get_finance_dashboard(): void
    {
        $this->getJson('/api/v1/finance/dashboard')
            ->assertUnauthorized();
    }
}
