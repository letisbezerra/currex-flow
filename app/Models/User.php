<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property UserRole $role
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'country',
        'currency_code',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'role' => UserRole::class,
        ];
    }

    public function isFinance(): bool
    {
        return $this->role === UserRole::Finance;
    }

    /** @return HasMany<PaymentRequest, $this> */
    public function paymentRequests(): HasMany
    {
        return $this->hasMany(PaymentRequest::class);
    }

    /** @return HasMany<PaymentRequest, $this> */
    public function reviewedPayments(): HasMany
    {
        return $this->hasMany(PaymentRequest::class, 'reviewed_by');
    }
}
