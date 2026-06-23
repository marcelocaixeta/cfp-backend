<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinanceSummaryController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'mes' => ['sometimes', 'date_format:Y-m'],
        ]);

        $timezone = config('app.timezone', 'UTC');
        $month = isset($data['mes'])
            ? CarbonImmutable::createFromFormat('Y-m', $data['mes'], $timezone)->startOfMonth()
            : CarbonImmutable::now($timezone)->startOfMonth();
        $startOfMonth = $month->toDateString();
        $endOfMonth = $month->endOfMonth()->toDateString();
        $user = $request->user();

        $salaryIncome = (float) $user->monthlyIncomes()
            ->whereBetween('data_recebimento', [$startOfMonth, $endOfMonth])
            ->where('tipo_receita', 'salary')
            ->sum('valor');

        $creditCardExpense = (float) $user->creditCardDebts()
            ->whereBetween('primeira_data_vencimento', [$startOfMonth, $endOfMonth])
            ->where('situacao', '!=', 'canceled')
            ->sum('valor_parcela');

        $loanInstallmentExpense = (float) $user->loanInstallments()
            ->whereBetween('data_vencimento', [$startOfMonth, $endOfMonth])
            ->where('situacao', '!=', 'canceled')
            ->sum('valor');

        $homeBillExpense = (float) $user->homeBills()
            ->whereBetween('data_vencimento', [$startOfMonth, $endOfMonth])
            ->where('situacao', '!=', 'canceled')
            ->sum('valor');

        $monthlyExpenseTotal = $creditCardExpense + $loanInstallmentExpense + $homeBillExpense;
        $monthlyBalance = $salaryIncome - $monthlyExpenseTotal;
        $expenseComposition = [
            [
                'chave' => 'dividas_cartao_credito',
                'rotulo' => 'Gastos com cartão',
                'valor' => $this->money($creditCardExpense),
            ],
            [
                'chave' => 'parcelas_emprestimos',
                'rotulo' => 'Parcelas de empréstimos',
                'valor' => $this->money($loanInstallmentExpense),
            ],
            [
                'chave' => 'contas_casa',
                'rotulo' => 'Contas de casa',
                'valor' => $this->money($homeBillExpense),
            ],
        ];

        return response()->json([
            'data' => [
                'controle_gastos_mensais' => [
                    'periodo' => [
                        'mes' => $month->format('Y-m'),
                        'inicio' => $startOfMonth,
                        'fim' => $endOfMonth,
                    ],
                    'salario_liquido' => $this->money($salaryIncome),
                    'total_gastos' => $this->money($monthlyExpenseTotal),
                    'saldo_previsto' => $this->money($monthlyBalance),
                    'gastando_mais_do_que_ganha' => $monthlyExpenseTotal > $salaryIncome,
                    'composicao_gastos' => $expenseComposition,
                    'grafico' => [
                        'tipo' => 'bar',
                        'itens' => [
                            [
                                'chave' => 'salario_liquido',
                                'rotulo' => 'Salário líquido',
                                'valor' => $this->money($salaryIncome),
                            ],
                            [
                                'chave' => 'total_gastos',
                                'rotulo' => 'Gastos do mês',
                                'valor' => $this->money($monthlyExpenseTotal),
                            ],
                        ],
                    ],
                ],
                'dividas_cartao_credito' => [
                    'total_pendente' => (string) $user->creditCardDebts()->where('situacao', 'pending')->sum('valor_total'),
                    'total_vencido' => (string) $user->creditCardDebts()->where('situacao', 'overdue')->sum('valor_total'),
                    'count' => $user->creditCardDebts()->count(),
                ],
                'emprestimos' => [
                    'total_principal_ativo' => (string) $user->loans()->where('situacao', 'active')->sum('valor_principal'),
                    'total_parcelas_pendentes' => (string) $user->loanInstallments()->where('situacao', 'pending')->sum('valor'),
                    'total_parcelas_vencidas' => (string) $user->loanInstallments()->where('situacao', 'overdue')->sum('valor'),
                ],
            ],
        ]);
    }

    private function money(float $value): string
    {
        return number_format($value, 2, '.', '');
    }
}
