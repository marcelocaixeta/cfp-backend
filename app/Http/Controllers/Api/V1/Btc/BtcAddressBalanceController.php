<?php

namespace App\Http\Controllers\Api\V1\Btc;

use App\Http\Controllers\Controller;
use App\Services\Bitcoin\BitcoinAddressBalanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class BtcAddressBalanceController extends Controller
{
    public function __invoke(Request $request, BitcoinAddressBalanceService $balances): JsonResponse
    {
        $validated = $request->validate(['address' => ['sometimes', ...$this->addressRules()]]);

        $address = $validated['address'] ?? config('services.bitcoin.wallet_address');

        if (! is_string($address) || trim($address) === '') {
            throw ValidationException::withMessages([
                'address' => 'Informe um endereco Bitcoin para consultar.',
            ]);
        }

        Validator::make(['address' => $address], [
            'address' => ['required', ...$this->addressRules()],
        ])->validate();

        try {
            return response()->json([
                'data' => $balances->getBalance($address),
            ]);
        } catch (RuntimeException) {
            return response()->json([
                'message' => 'Nao foi possivel consultar o saldo do endereco Bitcoin agora.',
            ], 502);
        }
    }

    /**
     * @return array<int, string>
     */
    private function addressRules(): array
    {
        return [
            'string',
            'max:90',
            'regex:/^([13][a-km-zA-HJ-NP-Z1-9]{25,34}|bc1[ac-hj-np-z02-9]{11,71})$/i',
        ];
    }
}
