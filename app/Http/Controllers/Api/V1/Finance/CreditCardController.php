<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Http\Controllers\Controller;
use App\Models\CreditCard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CreditCardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $request->user()->creditCards()->latest()->paginate(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $creditCard = $request->user()->creditCards()->create($this->validated($request));

        return response()->json(['data' => $creditCard], 201);
    }

    public function show(Request $request, CreditCard $creditCard): JsonResponse
    {
        $this->authorizeOwner($request, $creditCard);

        return response()->json(['data' => $creditCard]);
    }

    public function update(Request $request, CreditCard $creditCard): JsonResponse
    {
        $this->authorizeOwner($request, $creditCard);
        $creditCard->update($this->validated($request, true));

        return response()->json(['data' => $creditCard->refresh()]);
    }

    public function destroy(Request $request, CreditCard $creditCard): JsonResponse
    {
        $this->authorizeOwner($request, $creditCard);
        $creditCard->delete();

        return response()->json(status: 204);
    }

    private function validated(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'name' => [$required, 'string', 'max:255'],
            'last_four_digits' => ['nullable', 'digits:4'],
            'brand' => ['nullable', 'string', 'max:255'],
            'limit_amount' => ['nullable', 'numeric', 'min:0'],
            'closing_day' => ['nullable', 'integer', 'between:1,31'],
            'due_day' => ['nullable', 'integer', 'between:1,31'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }

    private function authorizeOwner(Request $request, CreditCard $creditCard): void
    {
        abort_unless($creditCard->user_id === $request->user()->id, 404);
    }
}
