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
                'credit_card_debts' => [
                    'pending_total' => (string) $user->creditCardDebts()->where('status', 'pending')->sum('total_amount'),
                    'overdue_total' => (string) $user->creditCardDebts()->where('status', 'overdue')->sum('total_amount'),
                    'count' => $user->creditCardDebts()->count(),
                ],
                'loans' => [
                    'active_principal_total' => (string) $user->loans()->where('status', 'active')->sum('principal_amount'),
                    'pending_installments_total' => (string) $user->loanInstallments()->where('status', 'pending')->sum('amount'),
                    'overdue_installments_total' => (string) $user->loanInstallments()->where('status', 'overdue')->sum('amount'),
                ],
            ],
        ]);
    }
}
