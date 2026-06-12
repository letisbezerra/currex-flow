<?php

declare(strict_types=1);

namespace Tests\Feature\Payment;

use App\Models\PaymentRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpirePaymentRequestsTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_requests_past_expiry_are_expired(): void
    {
        PaymentRequest::factory()->create(['expires_at' => now()->subHours(1)]);

        $this->artisan('payments:expire-pending')
            ->expectsOutput('Expired 1 payment request(s).')
            ->assertExitCode(0);

        $this->assertDatabaseHas('payment_requests', ['status' => 'expired']);
    }

    public function test_pending_requests_not_yet_expired_are_untouched(): void
    {
        $payment = PaymentRequest::factory()->create(['expires_at' => now()->addHours(48)]);

        $this->artisan('payments:expire-pending')
            ->expectsOutput('Expired 0 payment request(s).')
            ->assertExitCode(0);

        $this->assertDatabaseHas('payment_requests', [
            'id' => $payment->id,
            'status' => 'pending',
        ]);
    }

    public function test_approved_requests_past_expiry_are_not_expired(): void
    {
        PaymentRequest::factory()->approved()->create(['expires_at' => now()->subHours(1)]);

        $this->artisan('payments:expire-pending')
            ->expectsOutput('Expired 0 payment request(s).')
            ->assertExitCode(0);
    }

    public function test_rejected_requests_past_expiry_are_not_expired(): void
    {
        PaymentRequest::factory()->rejected()->create(['expires_at' => now()->subHours(1)]);

        $this->artisan('payments:expire-pending')
            ->expectsOutput('Expired 0 payment request(s).')
            ->assertExitCode(0);
    }

    public function test_command_reports_correct_count(): void
    {
        PaymentRequest::factory()->count(3)->create(['expires_at' => now()->subHours(1)]);
        PaymentRequest::factory()->create(['expires_at' => now()->addHours(48)]);

        $this->artisan('payments:expire-pending')
            ->expectsOutput('Expired 3 payment request(s).')
            ->assertExitCode(0);
    }
}
