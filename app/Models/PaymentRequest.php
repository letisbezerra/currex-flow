<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentRequest extends Model
{
    protected $fillable = [
        'user_id',
        'amount',
        'currency_code',
        'description',
        'status',
        'exchange_rate',
        'exchange_rate_source',
        'exchange_rate_fetched_at',
        'amount_in_eur',
        'reviewed_by',
        'reviewed_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'amount'                    => 'decimal:2',
            'exchange_rate'             => 'decimal:6',
            'amount_in_eur'             => 'decimal:2',
            'status'                    => PaymentStatus::class,
            'exchange_rate_fetched_at'  => 'datetime',
            'reviewed_at'               => 'datetime',
            'expires_at'                => 'datetime',
        ];
    }

    public function isPending(): bool
    {
        return $this->status === PaymentStatus::Pending;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
