<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['user_id', 'label', 'amount_btc', 'average_buy_price', 'currency'])]
class BtcAsset extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'amount_btc' => 'decimal:8',
            'average_buy_price' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
