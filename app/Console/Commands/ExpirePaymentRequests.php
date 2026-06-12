<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\PaymentStatus;
use App\Models\PaymentRequest;
use Illuminate\Console\Command;

class ExpirePaymentRequests extends Command
{
    protected $signature = 'payments:expire-pending';

    protected $description = 'Expire payment requests pending for more than 48 hours';

    public function handle(): int
    {
        $expired = PaymentRequest::where('status', PaymentStatus::Pending->value)
            ->where('expires_at', '<', now())
            ->update(['status' => PaymentStatus::Expired->value]);

        $this->info("Expired {$expired} payment request(s).");

        return Command::SUCCESS;
    }
}
