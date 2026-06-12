<?php

declare(strict_types=1);

namespace App\DTOs;

use DateTimeImmutable;

readonly class ExchangeRateDTO
{
    public function __construct(
        public string $from,
        public string $to,
        public string $rate,
        public DateTimeImmutable $fetchedAt,
    ) {}
}
