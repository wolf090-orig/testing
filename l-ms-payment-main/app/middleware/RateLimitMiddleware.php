<?php

namespace app\middleware;

use app\responses\JsonResponse;
use Webman\MiddlewareInterface;
use Webman\Http\Request;
use Webman\Http\Response;
use app\classes\Interfaces\RateLimitStorageInterface;
use support\Container;
use app\exception\AuthException;

class RateLimitMiddleware implements MiddlewareInterface
{
    /**
     * Максимальное количество запросов
     */
    private int $rateLimitAttempts;
    
    /**
     * Период ограничения в секундах
     */
    private int $rateLimitSeconds;
    
    /**
     * Сервис хранения для счетчиков ограничений
     */
    private RateLimitStorageInterface $storage;
    
    /**
     * Внутренние настройки лимитов для различных маршрутов
     * Форматы ключей:
     * 1. METHOD:/path - полный путь с указанием HTTP метода
     * 2. /path - полный путь без указания метода (для любого метода)
     */
    private static array $routeLimits = [
        // С указанием HTTP метода и полного пути
        'POST:/api/v1/auth/telegram' => 2,
        'GET:/api/v1/user/profile' => 20,
        'POST:/api/v1/user/settings' => 10,
        'GET:/api/v1/user/settings' => 10,
        
        // Без указания HTTP метода (для любого метода)
        '/api/v1/auth/telegram' => 15,
        '/api/v1/user/profile' => 40,
        '/healthcheck' => 100,
    ];
    
    /**
     * Групповые лимиты для частей путей
     * Применяются к любому маршруту, содержащему указанную строку
     */
    private static array $groupLimits = [
        'user' => 10,     // Все запросы, содержащие 'user' ограничены 10 запросами
        'auth' => 20,     // Запросы с 'auth' ограничены 20 запросами
        'settings' => 30, // Запросы с 'settings' ограничены 30 запросами
    ];
    
    /**
     * Конструктор middleware
     */
    public function __construct()
    {
        $this->rateLimitAttempts = config('app.rate_limit_attempts');
        $this->rateLimitSeconds = config('app.rate_limit_seconds');
        $this->storage = Container::get(RateLimitStorageInterface::class);
    }

    /**
     * Находит подходящий лимит для запроса
     * 
     * @param Request $request HTTP-запрос
     * @return array ['key' => string, 'limit' => int]
     */
    protected function findLimitForRequest(Request $request): array
    {
        $path = $request->path();
        $method = $request->method();
        
        // 1. Проверяем маршрут с методом (самый высокий приоритет)
        $routeKey = strtoupper($method) . ':' . $path;
        if (isset(self::$routeLimits[$routeKey])) {
            return ['key' => $routeKey, 'limit' => self::$routeLimits[$routeKey]];
        }
        
        // 2. Проверяем маршрут без метода
        if (isset(self::$routeLimits[$path])) {
            return ['key' => $path, 'limit' => self::$routeLimits[$path]];
        }
        
        // 3. Проверяем групповые лимиты (по вхождению строки)
        foreach (self::$groupLimits as $group => $limit) {
            if (stripos($path, $group) !== false) {
                return ['key' => "group:{$group}", 'limit' => $limit];
            }
        }
        
        // 4. Используем настройки по умолчанию
        return ['key' => 'default', 'limit' => $this->rateLimitAttempts];
    }

    /**
     * Формирует ключ для хранения счетчика запросов
     * 
     * @param string $limitKey Ключ лимита из findLimitForRequest
     * @param string $telegramId Telegram ID пользователя
     * @return string
     */
    protected function generateRateLimitKey(string $limitKey, string $telegramId): string
    {
        return "rate_limit:{$telegramId}:{$limitKey}";
    }
    
    /**
     * Добавляет заголовки с информацией о лимитах запросов
     * 
     * @param Response $response Объект ответа
     * @param int $limit Максимальное количество запросов
     * @param int $remaining Оставшееся количество запросов
     * @param int|null $retryAfter Время до сброса ограничения (в секундах)
     * @return Response
     */
    protected function addHeaders(Response $response, int $limit, int $remaining, ?int $retryAfter = null): Response
    {
        $response->withHeader('X-RateLimit-Limit', $limit)
                ->withHeader('X-RateLimit-Remaining', $remaining);
                
        if ($retryAfter !== null) {
            $response->withHeader('Retry-After', $retryAfter);
        }
        
        return $response;
    }

    /**
     * Обработка запроса и проверка лимитов
     * 
     * @param Request $request HTTP-запрос
     * @param callable $next Следующий обработчик
     * @return Response Ответ
     */
    public function process(Request $request, callable $next): Response
    {
        // Получаем telegram_id из request (установлен TelegramAuthMiddleware)
        $user = $request->telegramUser ?? null;
        $telegramId = $user->telegram_id ?? null;
        
        // Если нет авторизации, но требуется - бросаем исключение
        if (!$telegramId && request()->route() && request()->route()->getOption('middleware') 
            && in_array(TelegramAuthMiddleware::class, request()->route()->getOption('middleware'))) {
            throw new AuthException(AuthException::MSG_MISSING_TELEGRAM_ID, AuthException::ERROR_MISSING_TELEGRAM_ID);
        }
        
        // Определяем лимит для данного запроса
        $limitInfo = $this->findLimitForRequest($request);
        $limit = $limitInfo['limit'];
        
        // Формируем ключ с учетом лимита и пользователя
        $key = $this->generateRateLimitKey($limitInfo['key'], $telegramId);
        
        // Увеличиваем счетчик запросов
        $count = $this->storage->incr($key);
        if ($count === 1) {
            $this->storage->expire($key, $this->rateLimitSeconds);
        }
        
        // Проверяем превышение лимита
        if ($count > $limit) {
            // Получаем оставшееся время до сброса ограничения
            $retryAfter = $this->storage->ttl($key);
            if ($retryAfter <= 0) {
                $retryAfter = $this->rateLimitSeconds;
            }
            
            // Создаем ответ с HTTP-статусом 429 Too Many Requests
            $response = JsonResponse::error(
                [['message' => trans('response.rate_limit_exceeded')]], 
                trans('response.rate_limit_title'), 
                429
            );
            
            // Добавляем заголовки
            return $this->addHeaders($response, $limit, 0, $retryAfter);
        }
        
        // Выполняем запрос
        $response = $next($request);
        
        // Рассчитываем оставшееся количество запросов
        $remaining = max(0, $limit - $count);
        
        // Добавляем заголовки о лимитах запросов
        return $this->addHeaders($response, $limit, $remaining);
    }
} 