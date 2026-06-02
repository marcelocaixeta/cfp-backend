<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class LoanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $request->user()->loans()->withCount('installments')->latest()->paginate(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);

        $loan = DB::transaction(function () use ($request, $data): Loan {
            $loan = $request->user()->loans()->create($data);
            $firstDueDate = CarbonImmutable::parse($loan->first_due_date);

            for ($number = 1; $number <= $loan->installment_count; $number++) {
                $loan->installments()->create([
                    'user_id' => $request->user()->id,
                    'installment_number' => $number,
                    'due_date' => $firstDueDate->addMonthsNoOverflow($number - 1),
                    'amount' => $loan->installment_amount,
                    'status' => 'pending',
                ]);
            }

            return $loan;
        });

        return response()->json(['data' => $loan->load('installments')], 201);
    }

    public function show(Request $request, Loan $loan): JsonResponse
    {
        $this->authorizeOwner($request, $loan);

        return response()->json(['data' => $loan->load('installments')]);
    }

    public function update(Request $request, Loan $loan): JsonResponse
    {
        $this->authorizeOwner($request, $loan);
        $loan->update($this->validated($request, true));

        return response()->json(['data' => $loan->refresh()->load('installments')]);
    }

    public function destroy(Request $request, Loan $loan): JsonResponse
    {
        $this->authorizeOwner($request, $loan);
        $loan->delete();

        return response()->json(status: 204);
    }

    private function validated(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'category_id' => ['nullable', 'integer', 'exists:finance_categories,id'],
            'lender_name' => [$required, 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'principal_amount' => [$required, 'numeric', 'min:0.01'],
            'interest_rate' => ['nullable', 'numeric', 'min:0'],
            'interest_rate_period' => ['nullable', Rule::in(['monthly', 'yearly'])],
            'installment_count' => [$required, 'integer', 'min:1', 'max:360'],
            'installment_amount' => [$required, 'numeric', 'min:0.01'],
            'first_due_date' => [$required, 'date'],
            'status' => ['sometimes', Rule::in(['active', 'paid', 'overdue', 'canceled'])],
        ]);
    }

    private function authorizeOwner(Request $request, Loan $loan): void
    {
        abort_unless($loan->user_id === $request->user()->id, 404);
    }
}
