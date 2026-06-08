<?php

namespace App\Models;

use App\Models\Concerns\UsesPortugueseColumns;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['usuario_id', 'cartao_credito_id', 'categoria_id', 'descricao', 'valor_total', 'quantidade_parcelas', 'parcela_atual', 'valor_parcela', 'primeira_data_vencimento', 'situacao', 'observacoes'])]
class CreditCardDebt extends Model
{
    use SoftDeletes, UsesPortugueseColumns;

    protected $table = 'dividas_cartao_credito';

    protected function casts(): array
    {
        return [
            'valor_total' => 'decimal:2',
            'valor_parcela' => 'decimal:2',
            'primeira_data_vencimento' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function creditCard(): BelongsTo
    {
        return $this->belongsTo(CreditCard::class, 'cartao_credito_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(FinanceCategory::class, 'categoria_id');
    }
}
