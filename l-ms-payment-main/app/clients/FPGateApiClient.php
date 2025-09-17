<?php

declare(strict_types=1);

namespace app\clients;

use app\interfaces\PaymentGatewayInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use support\Log;

/**
 * HTTP клиент для FPGate API
 */
class FPGateApiClient implements PaymentGatewayInterface
{
    private Client $httpClient;
    private const API_VERSION = 'v1.6';

    public function __construct()
    {
        // Получаем общие настройки timeout из любой конфигурации
        $timeout = config('payment.fpgate_payin.timeout', 30);

        $this->httpClient = new Client([
            'timeout' => $timeout,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ]
        ]);
    }

    /**
     * Создать пополнение (PayIn)
     */
    public function createPayIn(array $data): array
    {
        // Получаем конфигурацию для PayIn
        $config = config('payment.fpgate_payin');
        
        $requestData = [
            'token' => $config['token'],
            'order_id' => $data['order_id'],
            'amount' => [
                'value' => $data['amount']['value'],
                'currency' => $data['amount']['currency']
            ],
            'customer' => [
                'id' => (string)$data['customer_id']
            ],
            'redirect' => 'false', // Обязательное поле согласно документации
            'description' => $data['description']
        ];

        // Добавляем callback_url если есть
        if (!empty($data['callback_url'])) {
            $requestData['callback_url'] = $data['callback_url'];
        }

        // Добавляем receipt если есть
        if (!empty($data['receipt'])) {
            $requestData['receipt'] = $data['receipt'];
        }

        // Формируем подпись для PayIn согласно документации FPGate
        // Порядок полей: token, order_id, amount.value, amount.currency, customer.id, redirect
        $signatureData =
            'token=' . $config['token'] .
            'order_id=' . $data['order_id'] .
            'amount.value=' . $data['amount']['value'] .
            'amount.currency=' . $data['amount']['currency'] .
            'customer.id=' . (string)$data['customer_id'] .
            'redirect=false';

        $requestData['signature'] = hash_hmac('sha256', $signatureData, $config['secret']);

        return $this->makeRequest('payin', $requestData, $config['base_url']);
    }

    /**
     * Создать выплату (PayOut)
     */
    public function createPayOut(array $data): array
    {
        // Получаем конфигурацию для PayOut
        $config = config('payment.fpgate_payout');
        
        $requestData = [
            'token' => $config['token'],
            'order_id' => $data['order_id'],
            'amount' => [
                'value' => $data['amount']['value'],
                'currency' => $data['amount']['currency']
            ],
            'description' => $data['description'],
            'details' => $data['details'], // Согласно документации FPGate
            'callback_url' => $data['callback_url']
        ];

        // Добавляем receipt если есть
        if (!empty($data['receipt'])) {
            $requestData['receipt'] = $data['receipt'];
        }

        // Формируем подпись для PayOut согласно документации
        // Порядок полей: token, order_id, amount.value, amount.currency
        // При наличии: details.card ИЛИ details.phone
        $signatureData =
            'token=' . $config['token'] .
            'order_id=' . $data['order_id'] .
            'amount.value=' . $data['amount']['value'] .
            'amount.currency=' . $data['amount']['currency'];

        // Добавляем поле details в подпись если есть
        if (isset($data['details']['card'])) {
            $signatureData .= 'details.card=' . $data['details']['card'];
        } elseif (isset($data['details']['phone'])) {
            $signatureData .= 'details.phone=' . $data['details']['phone'];
        }

        $requestData['signature'] = hash_hmac('sha256', $signatureData, $config['secret']);

        return $this->makeRequest('payout', $requestData, $config['base_url']);
    }

    /**
     * Получить статус транзакции
     */
    public function getStatus(string $transactionId): array
    {
        // Для статуса используем PayIn конфигурацию (может быть любая)
        $config = config('payment.fpgate_payin');
        
        $requestData = [
            'token' => $config['token'],
            'transaction_id' => $transactionId,
        ];

        // Формируем подпись для Status согласно документации
        // Порядок полей: token, transaction_id
        $signatureData =
            'token=' . $config['token'] .
            'transaction_id=' . $transactionId;

        $requestData['signature'] = hash_hmac('sha256', $signatureData, $config['secret']);

        return $this->makeRequest('status', $requestData, $config['base_url']);
    }

    /**
     * Получить баланс
     */
    public function getBalance(): array
    {
        // Для баланса используем PayIn конфигурацию (может быть любая)
        $config = config('payment.fpgate_payin');
        
        $requestData = [
            'token' => $config['token'],
        ];

        // Формируем подпись для Balance согласно документации
        // Порядок полей: token
        $signatureData = 'token=' . $config['token'];

        $requestData['signature'] = hash_hmac('sha256', $signatureData, $config['secret']);

        return $this->makeRequest('balance', $requestData, $config['base_url']);
    }



    /**
     * Формирует строку запроса для логирования
     */
    public function getRequestAsStringV2(string $method, string $uri, array $options = [])
    {
        $headers = $options['headers'] ?? [];
        $body = $options['json'] ?? $options['form_params'] ?? $options['multipart'] ?? null;
        $query = $options['query'] ?? null;

        $jsonFlags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR;
        $headers = json_encode($this->transformRequestHeaders($headers), $jsonFlags);
        $body = json_encode($body, $jsonFlags);
        $query = json_encode($query, $jsonFlags);
        
        // Логируем все в одну строку как в примере
        $logParts = [];
        $logParts[] = "REQUEST: $method $uri";
        $logParts[] = "Headers: $headers";
        if ($body && $body !== 'null') {
            $logParts[] = "Body: $body";
        }
        if ($query && $query !== 'null') {
            $logParts[] = "Query: $query";
        }
        
        return implode(' ', $logParts);
    }

    /**
     * Выполняет HTTP запрос к FPGate API с упрощенным логированием
     */
    private function makeRequest(string $operation, array $data, string $baseUrl): array
    {
        $url = $baseUrl . '/p2p_' . $operation;
        $headers = $this->httpClient->getConfig('headers');
        
        // Логируем запрос с помощью упрощенного метода БЕЗ маскирования
        $requestString = $this->getRequestAsStringV2('POST', $url, [
            'headers' => $headers,
            'json' => $data  // Без маскирования
        ]);
        
        Log::channel('payment_create')->info("FPGate API запрос: $operation # " . $requestString);

        try {
            $response = $this->httpClient->post($url, [
                'json' => $data
            ]);

            $statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents();
            $responseData = json_decode($body, true);

            Log::channel('payment_create')->info("FPGate API ответ: $operation", [
                'status_code' => $statusCode,
                'response_data' => $responseData
            ]);

            return [
                'success' => true,
                'status_code' => $statusCode,
                'data' => $responseData
            ];
        } catch (RequestException $e) {
            $statusCode = $e->getResponse() ? $e->getResponse()->getStatusCode() : 0;
            $body = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : '';
            $responseData = $body ? json_decode($body, true) : null;

            Log::channel('payment_create')->error("FPGate API ошибка: $operation", [
                'error_message' => $e->getMessage(),
                'status_code' => $statusCode,
                'response_data' => $responseData
            ]);

            return [
                'success' => false,
                'status_code' => $statusCode,
                'error' => $e->getMessage(),
                'data' => $responseData
            ];
        } catch (\Exception $e) {
            Log::channel('payment_create')->error("FPGate API ошибка: $operation", [
                'error_message' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'status_code' => 500,
                'error' => $e->getMessage(),
                'data' => null
            ];
        }
    }



    /**
     * Трансформирует заголовки для логирования (маскирует чувствительные)
     */
    private function transformRequestHeaders(array $headers): array
    {
        $sensitiveHeaders = [
            'authorization',
            'x-api-key',
            'x-auth-token',
            'cookie'
        ];

        $maskedHeaders = [];
        
        foreach ($headers as $key => $value) {
            $lowerKey = strtolower($key);
            
            if (in_array($lowerKey, $sensitiveHeaders)) {
                $maskedHeaders[$key] = '[UPDATED]';
            } else {
                $maskedHeaders[$key] = $value;
            }
        }
        
        return $maskedHeaders;
    }

    /**
     * Маскирует чувствительные данные в заголовках
     */
    private function maskSensitiveHeaders(array $headers): array
    {
        $sensitiveHeaders = [
            'authorization',
            'x-api-key',
            'x-auth-token',
            'cookie'
        ];

        $maskedHeaders = [];
        
        foreach ($headers as $key => $value) {
            $lowerKey = strtolower($key);
            
            if (in_array($lowerKey, $sensitiveHeaders)) {
                if ($lowerKey === 'authorization') {
                    $maskedHeaders[$key] = 'Bearer [FILTERED]';
                } else {
                    $maskedHeaders[$key] = '[FILTERED]';
                }
            } else {
                $maskedHeaders[$key] = $value;
            }
        }
        
        return $maskedHeaders;
    }

    /**
     * Убирает конфиденциальные данные из логов запроса
     */
    private function sanitizeLogData(array $data): array
    {
        $sanitized = $data;

        // Убираем подпись из логов
        if (isset($sanitized['signature'])) {
            $sanitized['signature'] = '***MASKED***';
        }

        // Маскируем токен (показываем только первые 8 символов)
        if (isset($sanitized['token'])) {
            $sanitized['token'] = substr($sanitized['token'], 0, 8) . '***';
        }

        return $sanitized;
    }
}
