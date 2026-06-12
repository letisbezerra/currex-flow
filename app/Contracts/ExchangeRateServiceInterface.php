<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTOs\ExchangeRateDTO;

interface ExchangeRateServiceInterface
{
    public function getRate(string $currencyCode): ExchangeRateDTO;
}
