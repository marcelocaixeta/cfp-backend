<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'default_currency', 'timezone', 'dashboard_preferences', 'notification_preferences'])]
class UserSetting extends Model
{
    protected function casts(): array
    {
        return [
            'dashboard_preferences' => 'array',
            'notification_preferences' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
