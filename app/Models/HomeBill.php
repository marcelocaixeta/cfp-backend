<?php

namespace App\Models;

use App\Models\Concerns\UsesPortugueseColumns;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['usuario_id', 'tipo', 'descricao', 'fornecedor_nome', 'valor', 'data_vencimento', 'mes_referencia', 'situacao', 'observacoes'])]
class HomeBill extends Model
{
    use SoftDeletes, UsesPortugueseColumns;

    protected $table = 'contas_casa';

    protected function casts(): array
    {
        return [
            'valor' => 'decimal:2',
            'data_vencimento' => 'date',
            'mes_referencia' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
