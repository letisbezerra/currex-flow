<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Exceptions\ExchangeRateException;
use App\Services\ExchangeRate\ExchangeRateApiService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ExchangeRateServiceTest extends TestCase
{
    private ExchangeRateApiService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ExchangeRateApiService;
    }

    public function test_returns_dto_with_correct_rate_and_source(): void
    {
        Http::fake([
            '*/EUR' => Http::response(['rates' => ['BRL' => 5.42, 'USD' => 1.10, 'EUR' => 1.0]], 200),
        ]);

        $dto = $this->service->getRate('BRL');

        $this->assertSame(5.42, $dto->rate);
        $this->assertSame('exchangerate-api.com', $dto->source);
        $this->assertInstanceOf(Carbon::class, $dto->fetchedAt);
    }

    public function test_throws_when_api_returns_error(): void
    {
        Http::fake([
            '*/EUR' => Http::response([], 503),
        ]);

        $this->expectException(ExchangeRateException::class);
        $this->expectExceptionMessage('Exchange rate service unavailable');

        $this->service->getRate('BRL');
    }

    public function test_throws_when_currency_not_in_response(): void
    {
        Http::fake([
            '*/EUR' => Http::response(['rates' => ['USD' => 1.10]], 200),
        ]);

        $this->expectException(ExchangeRateException::class);
        $this->expectExceptionMessage('Currency code XXX not supported');

        $this->service->getRate('XXX');
    }

    public function test_retries_on_transient_failure(): void
    {
        Http::fake([
            '*/EUR' => Http::sequence()
                ->push([], 503)
                ->push(['rates' => ['BRL' => 5.42]], 200),
        ]);

        $dto = $this->service->getRate('BRL');

        $this->assertSame(5.42, $dto->rate);
    }
}
