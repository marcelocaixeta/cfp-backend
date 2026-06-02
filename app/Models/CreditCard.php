<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['user_id', 'name', 'last_four_digits', 'brand', 'limit_amount', 'closing_day', 'due_day', 'is_active'])]
class CreditCard extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'limit_amount' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function debts(): HasMany
    {
        return $this->hasMany(CreditCardDebt::class);
    }
}
