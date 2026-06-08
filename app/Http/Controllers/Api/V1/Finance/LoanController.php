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
            $firstDueDate = CarbonImmutable::parse($loan->primeira_data_vencimento);

            for ($number = 1; $number <= $loan->quantidade_parcelas; $number++) {
                $loan->installments()->create([
                    'usuario_id' => $request->user()->id,
                    'numero_parcela' => $number,
                    'data_vencimento' => $firstDueDate->addMonthsNoOverflow($number - 1),
                    'valor' => $loan->valor_parcela,
                    'situacao' => 'pending',
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
            'categoria_id' => ['nullable', 'integer', 'exists:categorias_financeiras,id'],
            'credor_nome' => [$required, 'string', 'max:255'],
            'descricao' => ['nullable', 'string', 'max:255'],
            'valor_principal' => [$required, 'numeric', 'min:0.01'],
            'taxa_juros' => ['nullable', 'numeric', 'min:0'],
            'periodo_taxa_juros' => ['nullable', Rule::in(['monthly', 'yearly'])],
            'quantidade_parcelas' => [$required, 'integer', 'min:1', 'max:360'],
            'valor_parcela' => [$required, 'numeric', 'min:0.01'],
            'primeira_data_vencimento' => [$required, 'date'],
            'situacao' => ['sometimes', Rule::in(['active', 'paid', 'overdue', 'canceled'])],
        ]);
    }

    private function authorizeOwner(Request $request, Loan $loan): void
    {
        abort_unless($loan->usuario_id === $request->user()->id, 404);
    }
}
