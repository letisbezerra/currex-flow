<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\PaymentRequestController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register'])->middleware('throttle:auth');
        Route::post('login', [AuthController::class, 'login'])->middleware('throttle:auth');
        Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('payment-requests', [PaymentRequestController::class, 'index']);
        Route::post('payment-requests', [PaymentRequestController::class, 'store']);
        Route::get('payment-requests/{payment}', [PaymentRequestController::class, 'show']);
        Route::patch('payment-requests/{payment}/status', [PaymentRequestController::class, 'updateStatus']);
    });
});
