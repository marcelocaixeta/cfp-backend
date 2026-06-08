<?php

namespace App\Models;

use App\Models\Concerns\UsesPortugueseColumns;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['usuario_id', 'moeda_padrao', 'fuso_horario', 'preferencias_dashboard', 'preferencias_notificacao'])]
class UserSetting extends Model
{
    use UsesPortugueseColumns;

    protected $table = 'configuracoes_usuario';

    protected function casts(): array
    {
        return [
            'preferencias_dashboard' => 'array',
            'preferencias_notificacao' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
