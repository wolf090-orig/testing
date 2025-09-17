<?php

use app\controller\BasketController;
use app\controller\HealthcheckController;
use app\controller\LotteryController;
use app\middleware\TelegramAuthMiddleware;
use Webman\Route;

const TICKET_MANAGER_PREFIX = "/api/v1/ticket";
// Define a route for the health check endpoint
Route::any('/healthcheck', [HealthcheckController::class, 'index']);

Route::group(TICKET_MANAGER_PREFIX, function () {
    Route::get('/lotteries', [LotteryController::class, 'getLotteries']);
    Route::get('/tickets', [LotteryController::class, 'getTickets']);
    Route::get('/info', [LotteryController::class, 'getLotteryInfo']);

    Route::get('/basket', [BasketController::class, 'getBasket'])->middleware(TelegramAuthMiddleware::class);
    Route::post('/basket', [BasketController::class, 'addBasket'])->middleware(TelegramAuthMiddleware::class);
    Route::delete('/basket', [BasketController::class, 'destroyBasket'])->middleware(TelegramAuthMiddleware::class);
    Route::post('/basket/payment', [BasketController::class, 'payBasket'])->middleware(TelegramAuthMiddleware::class);
    Route::get('/user/tickets', [LotteryController::class, 'getUserTickets'])->middleware(TelegramAuthMiddleware::class);
    Route::get('/user/statistics', [LotteryController::class, 'getUserStatistics'])->middleware(TelegramAuthMiddleware::class);
    Route::get('/user/tickets/{id}', [LotteryController::class, 'getUserTicket'])->middleware(TelegramAuthMiddleware::class);
});
