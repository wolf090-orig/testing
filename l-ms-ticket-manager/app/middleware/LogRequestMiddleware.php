<?php

namespace app\middleware;

use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;
use support\Log;

/**
 * Middleware для логирования запросов и ответов
 */
class LogRequestMiddleware implements MiddlewareInterface
{
    /**
     * Массив путей, логирование которых нужно игнорировать
     */
    protected $ignoredPaths = [
        '/healthcheck',
        '/favicon.ico',
    ];

    /**
     * Метод для логирования
     * 
     * @param string $level Уровень логирования
     * @param string $message Сообщение
     * @param array $context Контекст
     */
    private function log($level, $message, $context = [])
    {
        if (config('app.request_logging', true)) {
            Log::channel('request')->$level($message, $context);
        }
    }

    /**
     * @param Request $request
     * @param callable $next
     * @return Response
     */
    public function process(Request $request, callable $next): Response
    {
        // Получаем путь запроса
        $path = $request->path();

        // Игнорируем определенные пути (например, healthcheck)
        if (in_array($path, $this->ignoredPaths)) {
            return $next($request);
        }

        // Получаем заголовки запроса (удаляем потенциально чувствительную информацию)
        $headers = $request->header();
        if (isset($headers['authorization'])) {
            $headers['authorization'] = 'Bearer [FILTERED]';
        }

        // Получаем тело запроса
        $content = $request->rawBody();
        $contentType = $request->header('content-type', '');

        // Декодируем тело запроса, если оно в JSON
        $bodyData = null;
        if (stripos($contentType, 'application/json') !== false && $content) {
            $bodyData = json_decode($content, true);
        }

        // Записываем информацию о запросе
        $requestTime = microtime(true);

        // Выполняем запрос
        $response = $next($request);

        // Рассчитываем время выполнения
        $executionTime = microtime(true) - $requestTime;

        // Получаем тело ответа
        $responseBody = $response->rawBody();
        $responseData = null;
        if ($responseBody && $response->getHeader('Content-Type') === 'application/json') {
            $responseData = json_decode($responseBody, true);
        }

        // Формируем данные для логирования
        $logData = [
            'request' => [
                'method' => $request->method(),
                'path' => $path,
                'query' => $request->queryString(),
                'headers' => $headers,
                'body' => $bodyData ?: $content,
                'time' => date('Y-m-d H:i:s'),
                'ip' => $request->getRealIp(),
            ],
            'response' => [
                'status' => $response->getStatusCode(),
                'headers' => $response->getHeaders(),
                'body' => $responseData ?: $responseBody,
            ],
            'execution_time' => round($executionTime * 1000, 2) . 'ms',
        ];

        // Логируем запрос и ответ
        $this->log('info', "HTTP Request: {$request->method()} {$path}", $logData);

        return $response;
    }
}
