<?php

namespace App\Models;

use App\Models\Concerns\UsesPortugueseColumns;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['email', 'origem', 'endereco_ip', 'agente_usuario'])]
class EmailSignup extends Model
{
    use HasFactory, UsesPortugueseColumns;

    protected $table = 'inscricoes_email';
}
