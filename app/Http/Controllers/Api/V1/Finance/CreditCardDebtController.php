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

        if ($request->filled('situacao')) {
            $query->where('situacao', $request->string('situacao'));
        }

        return response()->json(['data' => $query->paginate()]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $this->authorizeCreditCard($request, $data['cartao_credito_id'] ?? null);

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
        $this->authorizeCreditCard($request, $data['cartao_credito_id'] ?? null);
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
            'cartao_credito_id' => ['nullable', 'integer', 'exists:cartoes_credito,id'],
            'categoria_id' => ['nullable', 'integer', 'exists:categorias_financeiras,id'],
            'descricao' => [$required, 'string', 'max:255'],
            'valor_total' => [$required, 'numeric', 'min:0.01'],
            'quantidade_parcelas' => [$required, 'integer', 'min:1', 'max:360'],
            'parcela_atual' => ['sometimes', 'integer', 'min:1', 'max:360'],
            'valor_parcela' => [$required, 'numeric', 'min:0.01'],
            'primeira_data_vencimento' => [$required, 'date'],
            'situacao' => ['sometimes', Rule::in(['pending', 'paid', 'overdue', 'canceled'])],
            'observacoes' => ['nullable', 'string'],
        ]);
    }

    private function authorizeOwner(Request $request, CreditCardDebt $debt): void
    {
        abort_unless($debt->usuario_id === $request->user()->id, 404);
    }

    private function authorizeCreditCard(Request $request, ?int $creditCardId): void
    {
        if ($creditCardId === null) {
            return;
        }

        abort_unless(CreditCard::whereKey($creditCardId)->where('usuario_id', $request->user()->id)->exists(), 422);
    }
}
