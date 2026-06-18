<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinanceDashboardController extends Controller
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

        $creditCardTotal = (float) $user->creditCardDebts()
            ->whereBetween('primeira_data_vencimento', [$startOfMonth, $endOfMonth])
            ->whereIn('situacao', ['pending', 'overdue'])
            ->sum('valor_parcela');

        $loanInstallmentTotal = (float) $user->loanInstallments()
            ->whereBetween('data_vencimento', [$startOfMonth, $endOfMonth])
            ->whereIn('situacao', ['pending', 'overdue'])
            ->sum('valor');

        $homeBillTotal = (float) $user->homeBills()
            ->whereBetween('data_vencimento', [$startOfMonth, $endOfMonth])
            ->whereIn('situacao', ['pending', 'overdue'])
            ->sum('valor');

        $totalToPay = $creditCardTotal + $loanInstallmentTotal + $homeBillTotal;
        $totalIncome = (float) $user->monthlyIncomes()
            ->whereBetween('data_recebimento', [$startOfMonth, $endOfMonth])
            ->sum('valor');
        $salaryIncome = (float) $user->monthlyIncomes()
            ->whereBetween('data_recebimento', [$startOfMonth, $endOfMonth])
            ->where('tipo_receita', 'salary')
            ->sum('valor');

        $paymentComposition = [
            [
                'chave' => 'dividas_cartao_credito',
                'rotulo' => 'Gastos com cartão',
                'valor' => $this->money($creditCardTotal),
            ],
            [
                'chave' => 'parcelas_emprestimos',
                'rotulo' => 'Parcelas de empréstimos',
                'valor' => $this->money($loanInstallmentTotal),
            ],
            [
                'chave' => 'contas_casa',
                'rotulo' => 'Contas de casa',
                'valor' => $this->money($homeBillTotal),
            ],
        ];

        return response()->json([
            'data' => [
                'periodo' => [
                    'mes' => $month->format('Y-m'),
                    'inicio' => $startOfMonth,
                    'fim' => $endOfMonth,
                ],
                'totais' => [
                    'total_a_pagar' => $this->money($totalToPay),
                    'salario_liquido' => $this->money($salaryIncome),
                    'total_receitas' => $this->money($totalIncome),
                    'saldo' => $this->money($totalIncome - $totalToPay),
                ],
                'composicao_pagamentos' => $paymentComposition,
                'grafico' => [
                    'tipo' => 'pie',
                    'titulo' => 'Controle Financeiro Pessoal',
                    'itens' => array_values(array_filter([
                        [
                            'chave' => 'saldo',
                            'rotulo' => 'Saldo',
                            'valor' => $this->money(max($totalIncome - $totalToPay, 0)),
                        ],
                        ...$paymentComposition,
                    ], fn (array $item): bool => (float) $item['valor'] > 0)),
                ],
            ],
        ]);
    }

    private function money(float $value): string
    {
        return number_format($value, 2, '.', '');
    }
}
