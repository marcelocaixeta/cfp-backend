<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinanceSummaryController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'data' => [
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
}
