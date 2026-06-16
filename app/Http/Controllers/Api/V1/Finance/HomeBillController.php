<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Http\Controllers\Controller;
use App\Models\HomeBill;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HomeBillController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = $request->user()->homeBills()->latest();

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->string('tipo'));
        }

        if ($request->filled('situacao')) {
            $query->where('situacao', $request->string('situacao'));
        }

        return response()->json(['data' => $query->paginate()]);
    }

    public function store(Request $request): JsonResponse
    {
        $homeBill = $request->user()->homeBills()->create($this->validated($request));

        return response()->json(['data' => $homeBill->refresh()], 201);
    }

    public function show(Request $request, HomeBill $homeBill): JsonResponse
    {
        $this->authorizeOwner($request, $homeBill);

        return response()->json(['data' => $homeBill]);
    }

    public function update(Request $request, HomeBill $homeBill): JsonResponse
    {
        $this->authorizeOwner($request, $homeBill);
        $homeBill->update($this->validated($request, true));

        return response()->json(['data' => $homeBill->refresh()]);
    }

    public function destroy(Request $request, HomeBill $homeBill): JsonResponse
    {
        $this->authorizeOwner($request, $homeBill);
        $homeBill->delete();

        return response()->json(status: 204);
    }

    private function validated(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'tipo' => [$required, Rule::in(['agua', 'luz', 'telefone'])],
            'descricao' => [$required, 'string', 'max:255'],
            'fornecedor_nome' => ['nullable', 'string', 'max:255'],
            'valor' => [$required, 'numeric', 'min:0.01'],
            'data_vencimento' => [$required, 'date'],
            'mes_referencia' => ['nullable', 'date'],
            'situacao' => ['sometimes', Rule::in(['pending', 'paid', 'overdue', 'canceled'])],
            'observacoes' => ['nullable', 'string'],
        ]);
    }

    private function authorizeOwner(Request $request, HomeBill $homeBill): void
    {
        abort_unless($homeBill->usuario_id === $request->user()->id, 404);
    }
}
