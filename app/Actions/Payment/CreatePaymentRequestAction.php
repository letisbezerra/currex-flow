<?php

declare(strict_types=1);

namespace App\Actions\Payment;

use App\Contracts\ExchangeRateServiceInterface;
use App\Enums\PaymentStatus;
use App\Models\PaymentRequest;
use App\Models\User;

class CreatePaymentRequestAction
{
    public function __construct(
        private readonly ExchangeRateServiceInterface $exchangeRate,
    ) {}

    /** @param array<string, mixed> $data */
    public function __invoke(array $data, User $user): PaymentRequest
    {
        $rate = $this->exchangeRate->getRate($user->currency_code);

        return PaymentRequest::create([
            'user_id' => $user->id,
            'amount' => $data['amount'],
            'currency_code' => $user->currency_code,
            'description' => $data['description'],
            'status' => PaymentStatus::Pending,
            'exchange_rate' => $rate->rate,
            'exchange_rate_source' => $rate->source,
            'exchange_rate_fetched_at' => $rate->fetchedAt,
            'amount_in_eur' => round($data['amount'] / $rate->rate, 2),
            'expires_at' => now()->addHours(48),
        ]);
    }
}
