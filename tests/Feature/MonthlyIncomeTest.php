<?php

namespace Tests\Feature;

use App\Models\MonthlyIncome;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MonthlyIncomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_monthly_income(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/finance/income', [
            'descricao' => 'Salario',
            'valor' => '8500.00',
            'data_recebimento' => '2026-06-05',
            'recorrente' => true,
            'tipo_receita' => 'salary',
            'observacoes' => 'Empresa X',
        ])
            ->assertCreated()
            ->assertJsonPath('data.descricao', 'Salario')
            ->assertJsonPath('data.valor', '8500.00')
            ->assertJsonPath('data.tipo_receita', 'salary');

        $this->assertDatabaseHas('receitas_mensais', [
            'usuario_id' => $user->id,
            'descricao' => 'Salario',
            'valor' => '8500.00',
            'tipo_receita' => 'salary',
        ]);
    }

    public function test_tipo_receita_must_be_valid(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/finance/income', [
            'descricao' => 'Renda',
            'valor' => '500.00',
            'data_recebimento' => '2026-06-05',
            'tipo_receita' => 'invalid',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('tipo_receita');
    }

    public function test_authenticated_user_lists_only_own_incomes_and_can_filter_by_type(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        Sanctum::actingAs($user);

        MonthlyIncome::create([
            'usuario_id' => $user->id,
            'descricao' => 'Salario',
            'valor' => '8500.00',
            'data_recebimento' => '2026-06-05',
            'tipo_receita' => 'salary',
        ]);

        MonthlyIncome::create([
            'usuario_id' => $user->id,
            'descricao' => 'Freelance',
            'valor' => '2000.00',
            'data_recebimento' => '2026-06-10',
            'tipo_receita' => 'freelance',
        ]);

        MonthlyIncome::create([
            'usuario_id' => $otherUser->id,
            'descricao' => 'Salario outro',
            'valor' => '6000.00',
            'data_recebimento' => '2026-06-05',
            'tipo_receita' => 'salary',
        ]);

        $this->getJson('/api/v1/finance/income?tipo_receita=salary')
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.tipo_receita', 'salary')
            ->assertJsonPath('data.data.0.descricao', 'Salario');
    }

    public function test_user_cannot_see_income_from_another_user(): void
    {
        $owner = User::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $income = MonthlyIncome::create([
            'usuario_id' => $owner->id,
            'descricao' => 'Salario',
            'valor' => '5000.00',
            'data_recebimento' => '2026-06-05',
            'tipo_receita' => 'salary',
        ]);

        $this->getJson("/api/v1/finance/income/{$income->id}")
            ->assertNotFound();
    }
}
