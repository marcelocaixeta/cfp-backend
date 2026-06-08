<?php

namespace App\Models;

use App\Models\Concerns\UsesPortugueseColumns;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['usuario_id', 'categoria_id', 'credor_nome', 'descricao', 'valor_principal', 'taxa_juros', 'periodo_taxa_juros', 'quantidade_parcelas', 'valor_parcela', 'primeira_data_vencimento', 'situacao'])]
class Loan extends Model
{
    use SoftDeletes, UsesPortugueseColumns;

    protected $table = 'emprestimos';

    protected function casts(): array
    {
        return [
            'valor_principal' => 'decimal:2',
            'taxa_juros' => 'decimal:4',
            'valor_parcela' => 'decimal:2',
            'primeira_data_vencimento' => 'date',
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

    public function installments(): HasMany
    {
        return $this->hasMany(LoanInstallment::class, 'emprestimo_id');
    }
}
