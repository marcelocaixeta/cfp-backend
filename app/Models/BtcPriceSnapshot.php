<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['provider', 'currency', 'price', 'captured_at'])]
class BtcPriceSnapshot extends Model
{
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'captured_at' => 'datetime',
        ];
    }
}
