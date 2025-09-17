<?php

use Webman\Route;
use app\controller\PaymentController;
use app\controller\FPGateCallbackController;
use app\controller\HealthcheckController;
use app\middleware\InternalApiAuthMiddleware;
use app\middleware\FPGateCallbackMiddleware;

const PAYMENT_PREFIX = "/api/v1/payments";

// Healthcheck без миддлваров
Route::any('/healthcheck', [HealthcheckController::class, 'index']);

// Группа для платежных роутов
Route::group(PAYMENT_PREFIX, function () {
    // Callback от FPGate - С FPGateCallbackMiddleware
    Route::post("/fpgate/callback", [FPGateCallbackController::class, "handle"])
        ->middleware([FPGateCallbackMiddleware::class]);

    // Внутренние API для ms-ticket-manager - С Internal API middleware
    Route::get("/{internal_order_id}/status", [PaymentController::class, "getStatus"])
        ->middleware([InternalApiAuthMiddleware::class]);
    Route::get("/{internal_order_id}/check", [PaymentController::class, "checkExisting"])
        ->middleware([InternalApiAuthMiddleware::class]);

    // API для создания платежей - С Internal API middleware
    Route::post("/payin", [PaymentController::class, "createPayIn"])
        ->middleware([InternalApiAuthMiddleware::class]);
    Route::post("/payout", [PaymentController::class, "createPayOut"])
        ->middleware([InternalApiAuthMiddleware::class]);
    
    // API для получения баланса - С Internal API middleware
    Route::get("/balance", [PaymentController::class, "getBalance"])
        ->middleware([InternalApiAuthMiddleware::class]);
});
