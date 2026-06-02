<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Http\Controllers\Controller;
use App\Models\CreditCard;
use App\Models\CreditCardDebt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CreditCardDebtController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = $request->user()->creditCardDebts()->with('creditCard')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return response()->json(['data' => $query->paginate()]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $this->authorizeCreditCard($request, $data['credit_card_id'] ?? null);

        $debt = $request->user()->creditCardDebts()->create($data);

        return response()->json(['data' => $debt], 201);
    }

    public function show(Request $request, CreditCardDebt $creditCardDebt): JsonResponse
    {
        $this->authorizeOwner($request, $creditCardDebt);

        return response()->json(['data' => $creditCardDebt->load('creditCard')]);
    }

    public function update(Request $request, CreditCardDebt $creditCardDebt): JsonResponse
    {
        $this->authorizeOwner($request, $creditCardDebt);
        $data = $this->validated($request, true);
        $this->authorizeCreditCard($request, $data['credit_card_id'] ?? null);
        $creditCardDebt->update($data);

        return response()->json(['data' => $creditCardDebt->refresh()]);
    }

    public function destroy(Request $request, CreditCardDebt $creditCardDebt): JsonResponse
    {
        $this->authorizeOwner($request, $creditCardDebt);
        $creditCardDebt->delete();

        return response()->json(status: 204);
    }

    private function validated(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'credit_card_id' => ['nullable', 'integer', 'exists:credit_cards,id'],
            'category_id' => ['nullable', 'integer', 'exists:finance_categories,id'],
            'description' => [$required, 'string', 'max:255'],
            'total_amount' => [$required, 'numeric', 'min:0.01'],
            'installment_count' => [$required, 'integer', 'min:1', 'max:360'],
            'current_installment' => ['sometimes', 'integer', 'min:1', 'max:360'],
            'installment_amount' => [$required, 'numeric', 'min:0.01'],
            'first_due_date' => [$required, 'date'],
            'status' => ['sometimes', Rule::in(['pending', 'paid', 'overdue', 'canceled'])],
            'notes' => ['nullable', 'string'],
        ]);
    }

    private function authorizeOwner(Request $request, CreditCardDebt $debt): void
    {
        abort_unless($debt->user_id === $request->user()->id, 404);
    }

    private function authorizeCreditCard(Request $request, ?int $creditCardId): void
    {
        if ($creditCardId === null) {
            return;
        }

        abort_unless(CreditCard::whereKey($creditCardId)->where('user_id', $request->user()->id)->exists(), 422);
    }
}
