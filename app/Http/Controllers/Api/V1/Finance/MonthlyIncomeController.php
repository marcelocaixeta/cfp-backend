<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Http\Controllers\Controller;
use App\Models\MonthlyIncome;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MonthlyIncomeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = $request->user()->monthlyIncomes()->latest();

        if ($request->filled('tipo_receita')) {
            $query->where('tipo_receita', $request->string('tipo_receita'));
        }

        if ($request->filled('recorrente')) {
            $query->where('recorrente', $request->boolean('recorrente'));
        }

        return response()->json(['data' => $query->paginate()]);
    }

    public function store(Request $request): JsonResponse
    {
        $monthlyIncome = $request->user()->monthlyIncomes()->create($this->validated($request));

        return response()->json(['data' => $monthlyIncome->refresh()], 201);
    }

    public function show(Request $request, MonthlyIncome $monthlyIncome): JsonResponse
    {
        $this->authorizeOwner($request, $monthlyIncome);

        return response()->json(['data' => $monthlyIncome]);
    }

    public function update(Request $request, MonthlyIncome $monthlyIncome): JsonResponse
    {
        $this->authorizeOwner($request, $monthlyIncome);
        $monthlyIncome->update($this->validated($request, true));

        return response()->json(['data' => $monthlyIncome->refresh()]);
    }

    public function destroy(Request $request, MonthlyIncome $monthlyIncome): JsonResponse
    {
        $this->authorizeOwner($request, $monthlyIncome);
        $monthlyIncome->delete();

        return response()->json(status: 204);
    }

    private function validated(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'categoria_id' => ['nullable', 'integer', 'exists:categorias_financeiras,id'],
            'descricao' => [$required, 'string', 'max:255'],
            'valor' => [$required, 'numeric', 'min:0.01'],
            'data_recebimento' => [$required, 'date'],
            'recorrente' => ['sometimes', 'boolean'],
            'tipo_receita' => [$required, Rule::in(['salary', 'freelance', 'investment', 'other'])],
            'observacoes' => ['nullable', 'string'],
        ]);
    }

    private function authorizeOwner(Request $request, MonthlyIncome $monthlyIncome): void
    {
        abort_unless($monthlyIncome->usuario_id === $request->user()->id, 404);
    }
}
