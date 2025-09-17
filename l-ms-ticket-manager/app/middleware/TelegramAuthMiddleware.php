<?php

namespace app\middleware;

use app\services\TelegramAuthService;
use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;

class TelegramAuthMiddleware implements MiddlewareInterface
{
    public function process(Request $request, callable $next): Response
    {
        // Проверяем данные авторизации Telegram
        $authService = new TelegramAuthService();
        
        // Получаем telegram_id из данных авторизации
        // Не перехватываем исключения здесь - они будут обработаны глобальным обработчиком
        $telegramId = $authService->validateAndGetTelegramId($request);
        
        // Добавляем telegram_id в запрос
        $request->telegramId = $telegramId;
        
        return $next($request);
    }
} 