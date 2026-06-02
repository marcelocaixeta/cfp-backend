<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['email', 'source', 'ip_address', 'user_agent'])]
class EmailSignup extends Model
{
    use HasFactory;
}
