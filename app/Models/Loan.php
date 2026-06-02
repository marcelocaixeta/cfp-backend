<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['user_id', 'category_id', 'lender_name', 'description', 'principal_amount', 'interest_rate', 'interest_rate_period', 'installment_count', 'installment_amount', 'first_due_date', 'status'])]
class Loan extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'principal_amount' => 'decimal:2',
            'interest_rate' => 'decimal:4',
            'installment_amount' => 'decimal:2',
            'first_due_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(FinanceCategory::class, 'category_id');
    }

    public function installments(): HasMany
    {
        return $this->hasMany(LoanInstallment::class);
    }
}
