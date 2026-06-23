<?php

namespace Tests\Feature;

use App\Models\CreditCard;
use App\Models\CreditCardDebt;
use App\Models\HomeBill;
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

    public function test_authenticated_user_can_list_current_week_due_dates(): void
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

        HomeBill::create([
            'usuario_id' => $user->id,
            'tipo' => 'luz',
            'descricao' => 'Conta de energia',
            'fornecedor_nome' => 'Cemig',
            'valor' => '150.00',
            'data_vencimento' => '2026-06-11',
            'situacao' => 'overdue',
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
            ->assertJsonCount(1, 'data.contas_casa')
            ->assertJsonCount(1, 'data.parcelas_emprestimos')
            ->assertJsonPath('data.dividas_cartao_credito.0.descricao', 'Fatura do cartao')
            ->assertJsonPath('data.dividas_cartao_credito.0.data_vencimento', '2026-06-12')
            ->assertJsonPath('data.dividas_cartao_credito.0.cartao_credito.nome', 'Cartao principal')
            ->assertJsonPath('data.contas_casa.0.descricao', 'Conta de energia')
            ->assertJsonPath('data.contas_casa.0.fornecedor_nome', 'Cemig')
            ->assertJsonPath('data.contas_casa.0.data_vencimento', '2026-06-11')
            ->assertJsonPath('data.parcelas_emprestimos.0.credor_nome', 'Banco CFP')
            ->assertJsonPath('data.parcelas_emprestimos.0.data_vencimento', '2026-06-10')
            ->assertJsonPath('data.totais.dividas_cartao_credito.count', 1)
            ->assertJsonPath('data.totais.contas_casa.count', 1)
            ->assertJsonPath('data.totais.parcelas_emprestimos.count', 1);
    }

    public function test_authenticated_user_can_list_monthly_due_dates(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-10 10:00:00', config('app.timezone')));

        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        Sanctum::actingAs($user);

        CreditCardDebt::create([
            'usuario_id' => $user->id,
            'descricao' => 'Cartao dentro do mes',
            'valor_total' => '300.00',
            'quantidade_parcelas' => 3,
            'parcela_atual' => 1,
            'valor_parcela' => '100.00',
            'primeira_data_vencimento' => '2026-06-12',
            'situacao' => 'pending',
        ]);

        CreditCardDebt::create([
            'usuario_id' => $user->id,
            'descricao' => 'Cartao fora do mes',
            'valor_total' => '120.00',
            'valor_parcela' => '120.00',
            'primeira_data_vencimento' => '2026-07-02',
            'situacao' => 'pending',
        ]);

        CreditCardDebt::create([
            'usuario_id' => $otherUser->id,
            'descricao' => 'Cartao de outro usuario',
            'valor_total' => '80.00',
            'valor_parcela' => '80.00',
            'primeira_data_vencimento' => '2026-06-15',
            'situacao' => 'pending',
        ]);

        HomeBill::create([
            'usuario_id' => $user->id,
            'tipo' => 'agua',
            'descricao' => 'Agua dentro do mes',
            'valor' => '60.00',
            'data_vencimento' => '2026-06-12',
            'situacao' => 'pending',
        ]);

        HomeBill::create([
            'usuario_id' => $user->id,
            'tipo' => 'luz',
            'descricao' => 'Luz paga',
            'valor' => '110.00',
            'data_vencimento' => '2026-06-13',
            'situacao' => 'paid',
        ]);

        HomeBill::create([
            'usuario_id' => $user->id,
            'tipo' => 'telefone',
            'descricao' => 'Telefone cancelado',
            'valor' => '90.00',
            'data_vencimento' => '2026-06-14',
            'situacao' => 'canceled',
        ]);

        HomeBill::create([
            'usuario_id' => $user->id,
            'tipo' => 'agua',
            'descricao' => 'Agua fora do mes',
            'valor' => '80.00',
            'data_vencimento' => '2026-07-12',
            'situacao' => 'pending',
        ]);

        HomeBill::create([
            'usuario_id' => $otherUser->id,
            'tipo' => 'luz',
            'descricao' => 'Luz de outro usuario',
            'valor' => '70.00',
            'data_vencimento' => '2026-06-12',
            'situacao' => 'pending',
        ]);

        $loan = Loan::create([
            'usuario_id' => $user->id,
            'credor_nome' => 'Banco CFP',
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
                'data_vencimento' => '2026-06-20',
                'valor' => '100.00',
                'situacao' => 'pending',
            ],
            [
                'usuario_id' => $user->id,
                'numero_parcela' => 2,
                'data_vencimento' => '2026-06-22',
                'valor' => '100.00',
                'situacao' => 'paid',
            ],
            [
                'usuario_id' => $user->id,
                'numero_parcela' => 3,
                'data_vencimento' => '2026-07-20',
                'valor' => '100.00',
                'situacao' => 'pending',
            ],
        ]);

        $this->getJson('/api/v1/finance/current-week-due-dates?mes=2026-06')
            ->assertOk()
            ->assertJsonPath('data.periodo.inicio', '2026-06-01')
            ->assertJsonPath('data.periodo.fim', '2026-06-30')
            ->assertJsonCount(1, 'data.dividas_cartao_credito')
            ->assertJsonCount(1, 'data.contas_casa')
            ->assertJsonCount(1, 'data.parcelas_emprestimos')
            ->assertJsonPath('data.dividas_cartao_credito.0.descricao', 'Cartao dentro do mes')
            ->assertJsonPath('data.contas_casa.0.descricao', 'Agua dentro do mes')
            ->assertJsonPath('data.parcelas_emprestimos.0.credor_nome', 'Banco CFP')
            ->assertJsonPath('data.totais.dividas_cartao_credito.count', 1)
            ->assertJsonPath('data.totais.dividas_cartao_credito.valor', '100')
            ->assertJsonPath('data.totais.contas_casa.count', 1)
            ->assertJsonPath('data.totais.contas_casa.valor', '60')
            ->assertJsonPath('data.totais.parcelas_emprestimos.count', 1)
            ->assertJsonPath('data.totais.parcelas_emprestimos.valor', '100');
    }

    public function test_guest_cannot_list_current_week_due_dates(): void
    {
        $this->getJson('/api/v1/finance/current-week-due-dates')
            ->assertUnauthorized();
    }
}
