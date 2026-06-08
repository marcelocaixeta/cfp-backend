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
        abort_unless($loan->usuario_id === $request->user()->id, 404);

        return response()->json([
            'data' => $loan->installments()->orderBy('numero_parcela')->paginate(),
        ]);
    }

    public function update(Request $request, LoanInstallment $loanInstallment): JsonResponse
    {
        abort_unless($loanInstallment->usuario_id === $request->user()->id, 404);

        $data = $request->validate([
            'situacao' => ['sometimes', Rule::in(['pending', 'paid', 'overdue', 'canceled'])],
            'pago_em' => ['nullable', 'date'],
        ]);

        if (($data['situacao'] ?? null) === 'paid' && ! array_key_exists('pago_em', $data)) {
            $data['pago_em'] = now();
        }

        $loanInstallment->update($data);

        return response()->json(['data' => $loanInstallment->refresh()]);
    }
}
