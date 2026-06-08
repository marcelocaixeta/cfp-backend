<?php

namespace App\Models;

use App\Models\Concerns\UsesPortugueseColumns;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['emprestimo_id', 'usuario_id', 'numero_parcela', 'data_vencimento', 'valor', 'pago_em', 'situacao'])]
class LoanInstallment extends Model
{
    use UsesPortugueseColumns;

    protected $table = 'parcelas_emprestimos';

    protected function casts(): array
    {
        return [
            'data_vencimento' => 'date',
            'valor' => 'decimal:2',
            'pago_em' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class, 'emprestimo_id');
    }
}
