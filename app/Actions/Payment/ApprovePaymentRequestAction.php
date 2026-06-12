<?php

declare(strict_types=1);

namespace App\Actions\Payment;

use App\Enums\PaymentStatus;
use App\Models\PaymentRequest;
use App\Models\User;

class ApprovePaymentRequestAction
{
    public function __invoke(PaymentRequest $payment, User $reviewer): PaymentRequest
    {
        throw_unless(
            $payment->isPending(),
            \DomainException::class,
            'Only pending requests can be approved'
        );

        $payment->update([
            'status' => PaymentStatus::Approved,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);

        return $payment->fresh();
    }
}
