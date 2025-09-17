<?php

declare(strict_types=1);

namespace app\middleware;

use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;
use support\Log;

/**
 * Middleware для проверки внутреннего API токена между микросервисами
 * Обеспечивает безопасность API endpoints, доступных только для внутренних вызовов
 */
class InternalApiAuthMiddleware implements MiddlewareInterface
{
    /**
     * Обрабатывает запрос и проверяет наличие правильного токена авторизации
     */
    public function process(Request $request, callable $next): Response
    {
        // Получаем токен из заголовка Authorization
        $authHeader = $request->header('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return $this->unauthorizedResponse($request, 'отсутствует Bearer токен');
        }

        $receivedToken = substr($authHeader, 7); // Убираем "Bearer "

        // Получаем список разрешенных токенов
        $allowedTokens = config('integrations.internal_tokens', []);

        // Проверяем токен
        $isValidToken = false;
        $serviceName = null;

        foreach ($allowedTokens as $service => $token) {
            if (!empty($token) && $token === $receivedToken) {
                $isValidToken = true;
                $serviceName = $service;
                break;
            }
        }

        if (!$isValidToken) {
            return $this->unauthorizedResponse($request, 'неверный токен');
        }

        // Добавляем информацию о сервисе в запрос
        $request->service_name = $serviceName;

        // Передаем управление следующему middleware
        return $next($request);
    }

    /**
     * Возвращает ответ с ошибкой авторизации
     */
    private function unauthorizedResponse(Request $request, string $reason): Response
    {
        // Логируем попытку неавторизованного доступа
        Log::warning('Неавторизованный запрос к внутреннему API', [
            'reason' => $reason,
            'ip' => $request->getRealIp(),
            'url' => $request->url(),
            'method' => $request->method(),
            'user_agent' => $request->header('User-Agent'),
        ]);

        // Возвращаем ошибку 401
        return new Response(401, [
            'Content-Type' => 'application/json'
        ], json_encode([
            'error' => 'Unauthorized',
            'message' => 'Неверный или отсутствующий токен авторизации',
            'code' => 'INVALID_INTERNAL_TOKEN'
        ], JSON_UNESCAPED_UNICODE));
    }
}
