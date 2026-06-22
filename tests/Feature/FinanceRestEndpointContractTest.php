<?php

namespace Tests\Feature;

use App\Models\CreditCard;
use App\Models\CreditCardDebt;
use App\Models\HomeBill;
use App\Models\Loan;
use App\Models\MonthlyIncome;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FinanceRestEndpointContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_frontend_finance_rest_detail_endpoints_are_available(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $creditCard = CreditCard::create([
            'usuario_id' => $user->id,
            'nome' => 'Cartao principal',
            'ultimos_quatro_digitos' => '1234',
            'bandeira' => 'visa',
            'limite_valor' => '5000.00',
        ]);

        $this->getJson("/api/v1/finance/credit-cards/{$creditCard->id}")
            ->assertOk()
            ->assertJsonPath('data.nome', 'Cartao principal');

        $this->patchJson("/api/v1/finance/credit-cards/{$creditCard->id}", [
            'nome' => 'Cartao atualizado',
            'ativo' => false,
        ])
            ->assertOk()
            ->assertJsonPath('data.nome', 'Cartao atualizado')
            ->assertJsonPath('data.ativo', false);

        $homeBill = HomeBill::create([
            'usuario_id' => $user->id,
            'tipo' => 'agua',
            'descricao' => 'Conta de agua',
            'valor' => '120.00',
            'data_vencimento' => '2026-06-25',
        ]);

        $this->getJson("/api/v1/finance/home-bills/{$homeBill->id}")
            ->assertOk()
            ->assertJsonPath('data.descricao', 'Conta de agua');

        $this->patchJson("/api/v1/finance/home-bills/{$homeBill->id}", [
            'descricao' => 'Conta de agua atualizada',
            'situacao' => 'paid',
        ])
            ->assertOk()
            ->assertJsonPath('data.descricao', 'Conta de agua atualizada')
            ->assertJsonPath('data.situacao', 'paid');

        $loan = Loan::create([
            'usuario_id' => $user->id,
            'credor_nome' => 'Banco CFP',
            'descricao' => 'Emprestimo pessoal',
            'valor_principal' => '3000.00',
            'quantidade_parcelas' => 12,
            'valor_parcela' => '250.00',
            'primeira_data_vencimento' => '2026-07-10',
        ]);

        $this->getJson("/api/v1/finance/loans/{$loan->id}")
            ->assertOk()
            ->assertJsonPath('data.credor_nome', 'Banco CFP');

        $this->patchJson("/api/v1/finance/loans/{$loan->id}", [
            'descricao' => 'Emprestimo atualizado',
            'situacao' => 'paid',
        ])
            ->assertOk()
            ->assertJsonPath('data.descricao', 'Emprestimo atualizado')
            ->assertJsonPath('data.situacao', 'paid');

        $monthlyIncome = MonthlyIncome::create([
            'usuario_id' => $user->id,
            'descricao' => 'Salario',
            'valor' => '8500.00',
            'data_recebimento' => '2026-06-05',
            'tipo_receita' => 'salary',
        ]);

        $this->getJson("/api/v1/finance/receitas-mensais/{$monthlyIncome->id}")
            ->assertOk()
            ->assertJsonPath('data.descricao', 'Salario');

        $this->patchJson("/api/v1/finance/receitas-mensais/{$monthlyIncome->id}", [
            'descricao' => 'Salario atualizado',
            'valor' => '9000.00',
        ])
            ->assertOk()
            ->assertJsonPath('data.descricao', 'Salario atualizado')
            ->assertJsonPath('data.valor', '9000.00');

        $creditCardDebt = CreditCardDebt::create([
            'usuario_id' => $user->id,
            'cartao_credito_id' => $creditCard->id,
            'descricao' => 'Notebook',
            'valor_total' => '2400.00',
            'quantidade_parcelas' => 12,
            'parcela_atual' => 1,
            'valor_parcela' => '200.00',
            'primeira_data_vencimento' => '2026-06-20',
            'situacao' => 'pending',
        ]);

        $this->getJson("/api/v1/finance/credit-card-debts/{$creditCardDebt->id}")
            ->assertOk()
            ->assertJsonPath('data.descricao', 'Notebook')
            ->assertJsonPath('data.cartao_credito.id', $creditCard->id);

        $this->patchJson("/api/v1/finance/credit-card-debts/{$creditCardDebt->id}", [
            'descricao' => 'Notebook atualizado',
            'situacao' => 'paid',
        ])
            ->assertOk()
            ->assertJsonPath('data.descricao', 'Notebook atualizado')
            ->assertJsonPath('data.situacao', 'paid');

        $this->deleteJson("/api/v1/finance/credit-card-debts/{$creditCardDebt->id}")
            ->assertNoContent();

        $this->deleteJson("/api/v1/finance/receitas-mensais/{$monthlyIncome->id}")
            ->assertNoContent();

        $this->deleteJson("/api/v1/finance/loans/{$loan->id}")
            ->assertNoContent();

        $this->deleteJson("/api/v1/finance/home-bills/{$homeBill->id}")
            ->assertNoContent();

        $this->deleteJson("/api/v1/finance/credit-cards/{$creditCard->id}")
            ->assertNoContent();

        $this->assertSoftDeleted('dividas_cartao_credito', ['id' => $creditCardDebt->id], deletedAtColumn: 'excluido_em');
        $this->assertSoftDeleted('receitas_mensais', ['id' => $monthlyIncome->id], deletedAtColumn: 'excluido_em');
        $this->assertSoftDeleted('emprestimos', ['id' => $loan->id], deletedAtColumn: 'excluido_em');
        $this->assertSoftDeleted('contas_casa', ['id' => $homeBill->id], deletedAtColumn: 'excluido_em');
        $this->assertSoftDeleted('cartoes_credito', ['id' => $creditCard->id], deletedAtColumn: 'excluido_em');
    }
}
