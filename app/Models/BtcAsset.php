<?php

namespace App\Models;

use App\Models\Concerns\UsesPortugueseColumns;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['usuario_id', 'rotulo', 'tipo_ativo', 'quantidade_satoshis', 'preco_medio_compra', 'valor_investido', 'valor_atual', 'moeda'])]
class BtcAsset extends Model
{
    use SoftDeletes, UsesPortugueseColumns;

    protected $table = 'ativos_btc';

    protected function casts(): array
    {
        return [
            'quantidade_satoshis' => 'decimal:10',
            'preco_medio_compra' => 'decimal:2',
            'valor_investido' => 'decimal:2',
            'valor_atual' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
