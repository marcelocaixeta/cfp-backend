<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\LoanInstallment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LoanInstallmentController extends Controller
{
    public function index(Request $request, Loan $loan): JsonResponse
    {
        abort_unless($loan->user_id === $request->user()->id, 404);

        return response()->json([
            'data' => $loan->installments()->orderBy('installment_number')->paginate(),
        ]);
    }

    public function update(Request $request, LoanInstallment $loanInstallment): JsonResponse
    {
        abort_unless($loanInstallment->user_id === $request->user()->id, 404);

        $data = $request->validate([
            'status' => ['sometimes', Rule::in(['pending', 'paid', 'overdue', 'canceled'])],
            'paid_at' => ['nullable', 'date'],
        ]);

        if (($data['status'] ?? null) === 'paid' && ! array_key_exists('paid_at', $data)) {
            $data['paid_at'] = now();
        }

        $loanInstallment->update($data);

        return response()->json(['data' => $loanInstallment->refresh()]);
    }
}
