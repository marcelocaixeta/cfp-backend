<?php

namespace App\Http\Controllers\Api\V1\Btc;

use App\Http\Controllers\Controller;
use App\Models\BtcPriceSnapshot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BtcDashboardController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $currency = $request->query('moeda', 'BRL');
        $latestPrice = BtcPriceSnapshot::where('moeda', $currency)->latest('capturado_em')->first();
        $assets = $request->user()->btcAssets()->where('tipo_ativo', 'BTC')->get();
        $totalSatoshis = $assets->sum(fn ($asset): float => (float) $asset->quantidade_satoshis);
        $totalBtc = $totalSatoshis / 100_000_000;

        return response()->json([
            'data' => [
                'moeda' => $currency,
                'preco_mais_recente' => $latestPrice,
                'total_satoshis' => number_format($totalSatoshis, 10, '.', ''),
                'total_btc' => number_format($totalBtc, 8, '.', ''),
                'valor_estimado' => $latestPrice
                    ? number_format($totalBtc * (float) $latestPrice->preco, 2, '.', '')
                    : null,
                'ativos' => $assets,
            ],
        ]);
    }
}
