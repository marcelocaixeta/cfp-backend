<?php

namespace App\Models\Concerns;

trait UsesPortugueseColumns
{
    public const CREATED_AT = 'criado_em';

    public const UPDATED_AT = 'atualizado_em';

    public const DELETED_AT = 'excluido_em';
}
