<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Concerns\UsesPortugueseColumns;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['nome', 'email', 'senha'])]
#[Hidden(['senha', 'lembrar_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, UsesPortugueseColumns;

    protected $table = 'usuarios';

    protected $authPasswordName = 'senha';

    protected $rememberTokenName = 'lembrar_token';

    public function creditCards(): HasMany
    {
        return $this->hasMany(CreditCard::class, 'usuario_id');
    }

    public function creditCardDebts(): HasMany
    {
        return $this->hasMany(CreditCardDebt::class, 'usuario_id');
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class, 'usuario_id');
    }

    public function loanInstallments(): HasMany
    {
        return $this->hasMany(LoanInstallment::class, 'usuario_id');
    }

    public function btcAssets(): HasMany
    {
        return $this->hasMany(BtcAsset::class, 'usuario_id');
    }

    public function setting(): HasOne
    {
        return $this->hasOne(UserSetting::class, 'usuario_id');
    }

    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class, 'usuario_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verificado_em' => 'datetime',
            'senha' => 'hashed',
        ];
    }
}
