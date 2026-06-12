<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\PaymentRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentRequest>
 */
class PaymentRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'amount' => fake()->randomFloat(2, 10, 1000),
            'currency_code' => fake()->randomElement(['BRL', 'GBP', 'JPY', 'INR', 'CAD']),
            'description' => fake()->sentence(),
            'status' => PaymentStatus::Pending,
            'exchange_rate' => fake()->randomFloat(6, 0.5, 10),
            'exchange_rate_source' => 'exchangerate-api.com',
            'exchange_rate_fetched_at' => now(),
            'amount_in_eur' => fake()->randomFloat(2, 5, 500),
            'reviewed_by' => null,
            'reviewed_at' => null,
            'expires_at' => now()->addHours(48),
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => PaymentStatus::Approved,
            'reviewed_by' => User::factory()->finance(),
            'reviewed_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'status' => PaymentStatus::Rejected,
            'reviewed_by' => User::factory()->finance(),
            'reviewed_at' => now(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'status' => PaymentStatus::Expired,
            'expires_at' => now()->subHours(1),
        ]);
    }
}
