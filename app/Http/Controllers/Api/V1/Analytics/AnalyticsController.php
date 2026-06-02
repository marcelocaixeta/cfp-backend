<?php

namespace App\Http\Controllers\Api\V1\Analytics;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function overview(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'data' => [
                'credit_card_debts_count' => $user->creditCardDebts()->count(),
                'active_loans_count' => $user->loans()->where('status', 'active')->count(),
                'btc_assets_count' => $user->btcAssets()->count(),
                'open_support_tickets_count' => $user->supportTickets()->where('status', 'open')->count(),
            ],
        ]);
    }
}
