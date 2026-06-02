<?php

namespace App\Http\Controllers\Api\V1\Btc;

use App\Http\Controllers\Controller;
use App\Models\BtcAsset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BtcAssetController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $request->user()->btcAssets()->latest()->paginate(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $asset = $request->user()->btcAssets()->create($this->validated($request));

        return response()->json(['data' => $asset], 201);
    }

    public function show(Request $request, BtcAsset $btcAsset): JsonResponse
    {
        $this->authorizeOwner($request, $btcAsset);

        return response()->json(['data' => $btcAsset]);
    }

    public function update(Request $request, BtcAsset $btcAsset): JsonResponse
    {
        $this->authorizeOwner($request, $btcAsset);
        $btcAsset->update($this->validated($request, true));

        return response()->json(['data' => $btcAsset->refresh()]);
    }

    public function destroy(Request $request, BtcAsset $btcAsset): JsonResponse
    {
        $this->authorizeOwner($request, $btcAsset);
        $btcAsset->delete();

        return response()->json(status: 204);
    }

    private function validated(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'label' => [$required, 'string', 'max:255'],
            'amount_btc' => [$required, 'numeric', 'min:0'],
            'average_buy_price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
        ]);
    }

    private function authorizeOwner(Request $request, BtcAsset $btcAsset): void
    {
        abort_unless($btcAsset->user_id === $request->user()->id, 404);
    }
}
