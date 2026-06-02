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
        $currency = $request->query('currency', 'BRL');
        $latestPrice = BtcPriceSnapshot::where('currency', $currency)->latest('captured_at')->first();
        $assets = $request->user()->btcAssets()->get();
        $totalBtc = $assets->sum(fn ($asset): float => (float) $asset->amount_btc);

        return response()->json([
            'data' => [
                'currency' => $currency,
                'latest_price' => $latestPrice,
                'total_btc' => number_format($totalBtc, 8, '.', ''),
                'estimated_value' => $latestPrice
                    ? number_format($totalBtc * (float) $latestPrice->price, 2, '.', '')
                    : null,
                'assets' => $assets,
            ],
        ]);
    }
}
