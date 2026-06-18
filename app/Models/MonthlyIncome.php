<?php

namespace App\Models;

use App\Models\Concerns\UsesPortugueseColumns;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['usuario_id', 'categoria_id', 'descricao', 'valor', 'data_recebimento', 'recorrente', 'tipo_receita', 'observacoes'])]
class MonthlyIncome extends Model
{
    use SoftDeletes, UsesPortugueseColumns;

    protected $table = 'receitas_mensais';

    protected function casts(): array
    {
        return [
            'valor' => 'decimal:2',
            'data_recebimento' => 'date',
            'recorrente' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(FinanceCategory::class, 'categoria_id');
    }
}
