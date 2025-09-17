<?php

declare(strict_types=1);

namespace app\middleware;

use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;
use support\Log;

/**
 * Middleware для FPGate callback
 * 
 * Обрабатывает специфичные для FPGate валидации и предобработку
 * Пока заглушка - логику добавим позже
 */
class FPGateCallbackMiddleware implements MiddlewareInterface
{
    /**
     * Обрабатывает запрос callback от FPGate
     */
    public function process(Request $request, callable $next): Response
    {
        // TODO: Добавить логику валидации FPGate callback:
        // - Проверка User-Agent 
        // - Проверка IP адресов FPGate
        // - Rate limiting для callback
        // - Валидация базовой структуры данных
        // - Проверка обязательных полей

        // Логируем входящий callback
        Log::info('FPGate callback middleware: запрос получен', [
            'ip' => $request->getRealIp(),
            'user_agent' => $request->header('User-Agent'),
            'content_type' => $request->header('Content-Type'),
            'method' => $request->method(),
            'url' => $request->url(),
        ]);

        // Пока просто передаем управление дальше
        return $next($request);
    }
}
