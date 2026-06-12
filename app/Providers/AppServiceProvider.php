<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\ExchangeRateServiceInterface;
use App\Services\ExchangeRate\ExchangeRateApiService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            ExchangeRateServiceInterface::class,
            ExchangeRateApiService::class
        );
    }

    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(6)->by($request->ip());
        });
    }
}
