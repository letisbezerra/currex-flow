<?php

declare(strict_types=1);

namespace Tests\Feature\Payment;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CreatePaymentRequestTest extends TestCase
{
    use RefreshDatabase;

    private function fakeExchangeRate(float $rate = 5.42): void
    {
        Http::fake([
            '*/EUR' => Http::response(['rates' => ['BRL' => $rate, 'GBP' => 0.85, 'JPY' => 160.0, 'INR' => 90.0, 'CAD' => 1.45]], 200),
        ]);
    }

    public function test_employee_can_create_payment_request(): void
    {
        $this->fakeExchangeRate(5.42);

        $user = User::factory()->create(['currency_code' => 'BRL']);

        $response = $this->actingAs($user)->postJson('/api/v1/payment-requests', [
            'amount' => 500.00,
            'description' => 'Office supplies',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.currency_code', 'BRL')
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonStructure(['data' => [
                'id', 'amount', 'currency_code', 'amount_in_eur',
                'description', 'status', 'exchange_rate', 'exchange_rate_source',
                'exchange_rate_fetched_at', 'expires_at', 'created_at',
            ]]);

        $this->assertDatabaseHas('payment_requests', [
            'user_id' => $user->id,
            'currency_code' => 'BRL',
            'description' => 'Office supplies',
            'status' => 'pending',
        ]);
    }

    public function test_amount_in_eur_is_correctly_calculated(): void
    {
        $this->fakeExchangeRate(5.00);

        $user = User::factory()->create(['currency_code' => 'BRL']);

        $response = $this->actingAs($user)->postJson('/api/v1/payment-requests', [
            'amount' => 500.00,
            'description' => 'Travel expense',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.amount_in_eur', '100.00');
    }

    public function test_amount_is_required(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/v1/payment-requests', [
            'description' => 'Missing amount',
        ])->assertStatus(422)->assertJsonPath('errors.amount.0', 'The amount field is required.');
    }

    public function test_amount_must_be_positive(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/v1/payment-requests', [
            'amount' => -10,
            'description' => 'Negative amount',
        ])->assertStatus(422);
    }

    public function test_description_is_required(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/v1/payment-requests', [
            'amount' => 100,
        ])->assertStatus(422)->assertJsonPath('errors.description.0', 'The description field is required.');
    }

    public function test_unauthenticated_cannot_create(): void
    {
        $this->postJson('/api/v1/payment-requests', [
            'amount' => 100,
            'description' => 'Test',
        ])->assertStatus(401);
    }

    public function test_exchange_rate_service_down_returns_503(): void
    {
        Http::fake(['*/EUR' => Http::response([], 503)]);

        $user = User::factory()->create(['currency_code' => 'BRL']);

        $this->actingAs($user)->postJson('/api/v1/payment-requests', [
            'amount' => 100,
            'description' => 'Exchange down',
        ])->assertStatus(503)->assertJsonPath('message', 'Exchange rate service unavailable');
    }
}
