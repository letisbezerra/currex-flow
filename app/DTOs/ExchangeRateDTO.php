<?php

declare(strict_types=1);

namespace App\DTOs;

use Carbon\Carbon;

readonly class ExchangeRateDTO
{
    public function __construct(
        public float  $rate,
        public string $source,
        public Carbon $fetchedAt,
    ) {}
}
