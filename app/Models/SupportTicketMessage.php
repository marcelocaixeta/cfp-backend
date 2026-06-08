<?php

namespace App\Models;

use App\Models\Concerns\UsesPortugueseColumns;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['chamado_id', 'usuario_id', 'mensagem', 'interno'])]
class SupportTicketMessage extends Model
{
    use UsesPortugueseColumns;

    protected $table = 'mensagens_chamados_suporte';

    protected function casts(): array
    {
        return [
            'interno' => 'boolean',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'chamado_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
