<?php

declare(strict_types=1);

namespace App\Services\ExchangeRate;

use App\Contracts\ExchangeRateServiceInterface;
use App\DTOs\ExchangeRateDTO;
use App\Exceptions\ExchangeRateException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class ExchangeRateApiService implements ExchangeRateServiceInterface
{
    public function getRate(string $currencyCode): ExchangeRateDTO
    {
        try {
            $response = Http::timeout(10)
                ->retry(2, 500)
                ->get(config('services.exchange_rate.base_url').'/EUR');
        } catch (RequestException $e) {
            throw new ExchangeRateException('Exchange rate service unavailable', 0, $e);
        }

        throw_unless(
            $response->successful(),
            ExchangeRateException::class,
            'Exchange rate service unavailable'
        );

        $rates = $response->json('rates');

        throw_unless(
            isset($rates[$currencyCode]),
            ExchangeRateException::class,
            "Currency code {$currencyCode} not supported"
        );

        return new ExchangeRateDTO(
            rate: (float) $rates[$currencyCode],
            source: config('services.exchange_rate.source'),
            fetchedAt: now(),
        );
    }
}
