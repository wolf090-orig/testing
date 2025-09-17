<?php

namespace app\middleware;

use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;

/**
 * Middleware для проверки аутентификации пользователя
 */
class AuthMiddleware implements MiddlewareInterface
{
    public function __construct()
    {
    }

    /**
     * Обработка запроса для проверки аутентификации пользователя
     *
     * @param \support\Request $request
     * @param callable $handler
     * @return Response
     * @throws \Exception Если пользователь не аутентифицирован
     */
    public function process(Request $request, callable $handler): Response
    {
        $user = $request->user();
        if (empty($user)) {
            // Выбросить ошибку с кодом 401
            throw new \Exception("Unauthorized", 401);
        }

        return $handler($request);
    }
}
