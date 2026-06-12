<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PaymentRequest;
use App\Models\User;

class PaymentRequestPolicy
{
    public function view(User $user, PaymentRequest $payment): bool
    {
        return $user->isFinance() || $user->id === $payment->user_id;
    }

    public function updateStatus(User $user): bool
    {
        return $user->isFinance();
    }
}
