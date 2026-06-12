<?php

declare(strict_types=1);

namespace Tests\Feature\Payment;

use App\Models\PaymentRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdatePaymentStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_finance_can_approve_pending_request(): void
    {
        $finance = User::factory()->finance()->create();
        $payment = PaymentRequest::factory()->create();

        $response = $this->actingAs($finance)->patchJson(
            "/api/v1/payment-requests/{$payment->id}/status",
            ['status' => 'approved']
        );

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'approved');

        $this->assertDatabaseHas('payment_requests', [
            'id' => $payment->id,
            'status' => 'approved',
            'reviewed_by' => $finance->id,
        ]);
    }

    public function test_finance_can_reject_pending_request(): void
    {
        $finance = User::factory()->finance()->create();
        $payment = PaymentRequest::factory()->create();

        $response = $this->actingAs($finance)->patchJson(
            "/api/v1/payment-requests/{$payment->id}/status",
            ['status' => 'rejected']
        );

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'rejected');

        $this->assertDatabaseHas('payment_requests', [
            'id' => $payment->id,
            'status' => 'rejected',
        ]);
    }

    public function test_employee_cannot_update_status(): void
    {
        $employee = User::factory()->create();
        $payment = PaymentRequest::factory()->create(['user_id' => $employee->id]);

        $this->actingAs($employee)->patchJson(
            "/api/v1/payment-requests/{$payment->id}/status",
            ['status' => 'approved']
        )->assertStatus(403);
    }

    public function test_cannot_approve_already_approved_request(): void
    {
        $finance = User::factory()->finance()->create();
        $payment = PaymentRequest::factory()->approved()->create();

        $this->actingAs($finance)->patchJson(
            "/api/v1/payment-requests/{$payment->id}/status",
            ['status' => 'approved']
        )->assertStatus(409)
            ->assertJsonPath('message', 'Only pending requests can be approved');
    }

    public function test_cannot_reject_already_rejected_request(): void
    {
        $finance = User::factory()->finance()->create();
        $payment = PaymentRequest::factory()->rejected()->create();

        $this->actingAs($finance)->patchJson(
            "/api/v1/payment-requests/{$payment->id}/status",
            ['status' => 'rejected']
        )->assertStatus(409)
            ->assertJsonPath('message', 'Only pending requests can be rejected');
    }

    public function test_status_field_is_required(): void
    {
        $finance = User::factory()->finance()->create();
        $payment = PaymentRequest::factory()->create();

        $this->actingAs($finance)->patchJson(
            "/api/v1/payment-requests/{$payment->id}/status",
            []
        )->assertStatus(422);
    }

    public function test_status_must_be_approved_or_rejected(): void
    {
        $finance = User::factory()->finance()->create();
        $payment = PaymentRequest::factory()->create();

        $this->actingAs($finance)->patchJson(
            "/api/v1/payment-requests/{$payment->id}/status",
            ['status' => 'pending']
        )->assertStatus(422);
    }

    public function test_unauthenticated_cannot_update_status(): void
    {
        $payment = PaymentRequest::factory()->create();

        $this->patchJson(
            "/api/v1/payment-requests/{$payment->id}/status",
            ['status' => 'approved']
        )->assertStatus(401);
    }
}
