<?php

declare(strict_types=1);

namespace Tests\Feature\Payment;

use App\Models\PaymentRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListPaymentRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_sees_only_own_requests(): void
    {
        $employee = User::factory()->create();
        $other = User::factory()->create();

        PaymentRequest::factory()->count(2)->create(['user_id' => $employee->id]);
        PaymentRequest::factory()->count(3)->create(['user_id' => $other->id]);

        $response = $this->actingAs($employee)->getJson('/api/v1/payment-requests');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_finance_sees_all_requests(): void
    {
        $finance = User::factory()->finance()->create();
        $employee = User::factory()->create();

        PaymentRequest::factory()->count(3)->create(['user_id' => $employee->id]);

        $response = $this->actingAs($finance)->getJson('/api/v1/payment-requests');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));
    }

    public function test_status_filter_returns_matching_requests(): void
    {
        $employee = User::factory()->create();

        PaymentRequest::factory()->count(2)->create(['user_id' => $employee->id]);
        PaymentRequest::factory()->approved()->create(['user_id' => $employee->id]);

        $response = $this->actingAs($employee)
            ->getJson('/api/v1/payment-requests?status=pending');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_results_are_paginated(): void
    {
        $employee = User::factory()->create();
        PaymentRequest::factory()->count(20)->create(['user_id' => $employee->id]);

        $response = $this->actingAs($employee)->getJson('/api/v1/payment-requests');

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'meta' => ['current_page', 'per_page', 'total']]);

        $this->assertCount(15, $response->json('data'));
    }

    public function test_unauthenticated_cannot_list(): void
    {
        $this->getJson('/api/v1/payment-requests')->assertStatus(401);
    }

    public function test_employee_can_view_own_request(): void
    {
        $employee = User::factory()->create();
        $payment = PaymentRequest::factory()->create(['user_id' => $employee->id]);

        $this->actingAs($employee)
            ->getJson("/api/v1/payment-requests/{$payment->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $payment->id);
    }

    public function test_employee_cannot_view_others_request(): void
    {
        $employee = User::factory()->create();
        $other = User::factory()->create();
        $payment = PaymentRequest::factory()->create(['user_id' => $other->id]);

        $this->actingAs($employee)
            ->getJson("/api/v1/payment-requests/{$payment->id}")
            ->assertStatus(403);
    }

    public function test_finance_can_view_any_request(): void
    {
        $finance = User::factory()->finance()->create();
        $employee = User::factory()->create();
        $payment = PaymentRequest::factory()->create(['user_id' => $employee->id]);

        $this->actingAs($finance)
            ->getJson("/api/v1/payment-requests/{$payment->id}")
            ->assertStatus(200);
    }

    public function test_show_nonexistent_request_returns_404(): void
    {
        $user = User::factory()->finance()->create();

        $this->actingAs($user)
            ->getJson('/api/v1/payment-requests/9999')
            ->assertStatus(404);
    }
}
