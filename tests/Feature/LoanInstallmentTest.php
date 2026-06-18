<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LoanInstallmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_mark_loan_installment_as_paid(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $installment = $this->createInstallmentFor($user);

        $this->patchJson("/api/v1/finance/loan-installments/{$installment->id}/pay", [
            'pago_em' => '2026-06-18',
        ])
            ->assertOk()
            ->assertJsonPath('data.situacao', 'paid')
            ->assertJsonPath('data.pago_em', '2026-06-18T00:00:00.000000Z');

        $this->assertDatabaseHas('parcelas_emprestimos', [
            'id' => $installment->id,
            'usuario_id' => $user->id,
            'situacao' => 'paid',
        ]);

        $this->assertNotNull($installment->refresh()->pago_em);
    }

    public function test_user_cannot_mark_another_users_loan_installment_as_paid(): void
    {
        $owner = User::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $installment = $this->createInstallmentFor($owner);

        $this->patchJson("/api/v1/finance/loan-installments/{$installment->id}/pay")
            ->assertNotFound();

        $this->assertDatabaseHas('parcelas_emprestimos', [
            'id' => $installment->id,
            'situacao' => 'pending',
            'pago_em' => null,
        ]);
    }

    private function createInstallmentFor(User $user): LoanInstallment
    {
        $loan = Loan::create([
            'usuario_id' => $user->id,
            'credor_nome' => 'Banco CFP',
            'descricao' => 'Emprestimo pessoal',
            'valor_principal' => '3000.00',
            'quantidade_parcelas' => 3,
            'valor_parcela' => '1000.00',
            'primeira_data_vencimento' => '2026-06-16',
        ]);

        return LoanInstallment::create([
            'emprestimo_id' => $loan->id,
            'usuario_id' => $user->id,
            'numero_parcela' => 1,
            'data_vencimento' => '2026-06-16',
            'valor' => '1000.00',
            'situacao' => 'pending',
        ]);
    }
}
