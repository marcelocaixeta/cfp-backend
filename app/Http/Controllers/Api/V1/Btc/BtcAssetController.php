<?php

namespace App\Http\Controllers\Api\V1\Btc;

use App\Http\Controllers\Controller;
use App\Models\BtcAsset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BtcAssetController extends Controller
{
    private const TIPOS_ATIVO = ['BTC', 'RENDA_FIXA', 'RENDA_VARIAVEL'];

    public function index(Request $request): JsonResponse
    {
        $query = $request->user()->btcAssets()->latest();

        if ($request->filled('tipo_ativo')) {
            $query->where('tipo_ativo', $this->normalizeTipoAtivo($request->string('tipo_ativo')->toString()));
        }

        return response()->json([
            'data' => $query->paginate(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $asset = $request->user()->btcAssets()->create($this->validated($request));

        return response()->json(['data' => $asset->refresh()], 201);
    }

    public function show(Request $request, BtcAsset $btcAsset): JsonResponse
    {
        $this->authorizeOwner($request, $btcAsset);

        return response()->json(['data' => $btcAsset]);
    }

    public function update(Request $request, BtcAsset $btcAsset): JsonResponse
    {
        $this->authorizeOwner($request, $btcAsset);
        $btcAsset->update($this->validated($request, true));

        return response()->json(['data' => $btcAsset->refresh()]);
    }

    public function destroy(Request $request, BtcAsset $btcAsset): JsonResponse
    {
        $this->authorizeOwner($request, $btcAsset);
        $btcAsset->delete();

        return response()->json(status: 204);
    }

    private function validated(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';
        $tipoAtivoInput = $request->input('tipo_ativo', 'BTC');
        $tipoAtivo = is_string($tipoAtivoInput) ? $this->normalizeTipoAtivo($tipoAtivoInput) : $tipoAtivoInput;
        $quantidadeSatoshisRule = (! $partial && $tipoAtivo === 'BTC') ? 'required' : 'nullable';
        $valorAtualRule = (! $partial && $tipoAtivo !== 'BTC') ? 'required' : 'nullable';

        if ($request->has('tipo_ativo') && is_string($tipoAtivo)) {
            $request->merge(['tipo_ativo' => $tipoAtivo]);
        }

        return $request->validate([
            'rotulo' => [$required, 'string', 'max:255'],
            'tipo_ativo' => ['sometimes', 'string', Rule::in(self::TIPOS_ATIVO)],
            'quantidade_satoshis' => [$quantidadeSatoshisRule, 'numeric', 'min:0', 'decimal:0,10'],
            'preco_medio_compra' => ['nullable', 'numeric', 'min:0'],
            'valor_investido' => ['nullable', 'numeric', 'min:0'],
            'valor_atual' => [$valorAtualRule, 'numeric', 'min:0'],
            'moeda' => ['sometimes', 'string', 'size:3'],
        ]);
    }

    private function normalizeTipoAtivo(string $tipoAtivo): string
    {
        return match (mb_strtolower(trim($tipoAtivo))) {
            'btc' => 'BTC',
            'renda fixa', 'renda_fixa' => 'RENDA_FIXA',
            'renda variavel', 'renda variável', 'renda_variavel' => 'RENDA_VARIAVEL',
            default => strtoupper(trim($tipoAtivo)),
        };
    }

    private function authorizeOwner(Request $request, BtcAsset $btcAsset): void
    {
        abort_unless($btcAsset->usuario_id === $request->user()->id, 404);
    }
}
