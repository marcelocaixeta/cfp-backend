<?php

namespace App\Models;

use App\Models\Concerns\UsesPortugueseColumns;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['provedor', 'moeda', 'preco', 'capturado_em'])]
class BtcPriceSnapshot extends Model
{
    use UsesPortugueseColumns;

    protected $table = 'capturas_precos_btc';

    protected function casts(): array
    {
        return [
            'preco' => 'decimal:2',
            'capturado_em' => 'datetime',
        ];
    }
}
