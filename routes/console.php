<?php

declare(strict_types=1);

use App\Console\Commands\ExpirePaymentRequests;
use Illuminate\Support\Facades\Schedule;

Schedule::command(ExpirePaymentRequests::class)->hourly();
