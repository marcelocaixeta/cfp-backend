<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CurrentWeekDueDateController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $timezone = config('app.timezone', 'UTC');
        $startOfWeek = CarbonImmutable::now($timezone)->startOfWeek()->startOfDay();
        $endOfWeek = $startOfWeek->endOfWeek()->endOfDay();
        $user = $request->user();

        $debts = $user->creditCardDebts()
            ->with('creditCard:id,nome')
            ->whereBetween('primeira_data_vencimento', [$startOfWeek->toDateString(), $endOfWeek->toDateString()])
            ->whereIn('situacao', ['pending', 'overdue'])
            ->orderBy('primeira_data_vencimento')
            ->orderBy('id')
            ->get()
            ->map(fn ($debt): array => [
                'id' => $debt->id,
                'tipo' => 'divida_cartao_credito',
                'descricao' => $debt->descricao,
                'data_vencimento' => $debt->primeira_data_vencimento->toDateString(),
                'valor' => (string) $debt->valor_parcela,
                'valor_total' => (string) $debt->valor_total,
                'parcela_atual' => $debt->parcela_atual,
                'quantidade_parcelas' => $debt->quantidade_parcelas,
                'situacao' => $debt->situacao,
                'cartao_credito' => $debt->creditCard ? [
                    'id' => $debt->creditCard->id,
                    'nome' => $debt->creditCard->nome,
                ] : null,
            ]);

        $loanInstallments = $user->loanInstallments()
            ->with('loan:id,credor_nome,descricao')
            ->whereBetween('data_vencimento', [$startOfWeek->toDateString(), $endOfWeek->toDateString()])
            ->whereIn('situacao', ['pending', 'overdue'])
            ->orderBy('data_vencimento')
            ->orderBy('id')
            ->get()
            ->map(fn ($installment): array => [
                'id' => $installment->id,
                'tipo' => 'parcela_emprestimo',
                'emprestimo_id' => $installment->emprestimo_id,
                'credor_nome' => $installment->loan->credor_nome,
                'descricao' => $installment->loan->descricao,
                'data_vencimento' => $installment->data_vencimento->toDateString(),
                'valor' => (string) $installment->valor,
                'numero_parcela' => $installment->numero_parcela,
                'situacao' => $installment->situacao,
            ]);

        return response()->json([
            'data' => [
                'periodo' => [
                    'inicio' => $startOfWeek->toDateString(),
                    'fim' => $endOfWeek->toDateString(),
                ],
                'dividas_cartao_credito' => $debts,
                'parcelas_emprestimos' => $loanInstallments,
                'totais' => [
                    'dividas_cartao_credito' => [
                        'count' => $debts->count(),
                        'valor' => (string) $debts->sum(fn (array $debt): float => (float) $debt['valor']),
                    ],
                    'parcelas_emprestimos' => [
                        'count' => $loanInstallments->count(),
                        'valor' => (string) $loanInstallments->sum(fn (array $installment): float => (float) $installment['valor']),
                    ],
                ],
            ],
        ]);
    }
}
