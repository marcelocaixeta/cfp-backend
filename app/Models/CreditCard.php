<?php

namespace App\Models;

use App\Models\Concerns\UsesPortugueseColumns;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['usuario_id', 'nome', 'ultimos_quatro_digitos', 'bandeira', 'limite_valor', 'dia_fechamento', 'dia_vencimento', 'ativo'])]
class CreditCard extends Model
{
    use SoftDeletes, UsesPortugueseColumns;

    protected $table = 'cartoes_credito';

    protected function casts(): array
    {
        return [
            'limite_valor' => 'decimal:2',
            'ativo' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function debts(): HasMany
    {
        return $this->hasMany(CreditCardDebt::class, 'cartao_credito_id');
    }
}
