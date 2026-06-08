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
                'quantidade_dividas_cartao_credito' => $user->creditCardDebts()->count(),
                'quantidade_emprestimos_ativos' => $user->loans()->where('situacao', 'active')->count(),
                'quantidade_ativos_btc' => $user->btcAssets()->count(),
                'quantidade_chamados_suporte_abertos' => $user->supportTickets()->where('situacao', 'open')->count(),
            ],
        ]);
    }
}
