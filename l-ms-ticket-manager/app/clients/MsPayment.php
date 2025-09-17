<?php

namespace app\clients;

use app\classes\Interfaces\MsPaymentInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use support\Log;

class MsPayment implements MsPaymentInterface
{
    public const string PAYMENT_URI_PREFIX = "/api/v1/payments";

    private Client $client;
    private array $config;

    public function __construct()
    {
        $this->config = config('integrations.ms_payment');

        // Обеспечиваем корректный формат Bearer токена
        $token = $this->config['token'];
        if (!str_starts_with($token, 'Bearer ')) {
            $token = 'Bearer ' . $token;
        }

        $this->client = new Client([
            'base_uri' => $this->config['base_uri'],
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => $token
            ]
        ]);
    }

    /**
     * Создает пополнение (PayIn)
     */
    public function createPayIn(
        string $internalOrderId,
        int $userId,
        int $amount,
        string $currency,
        string $paymentMethod,
        array $details = [],
        ?array $receipt = null
    ): array {
        try {
            $requestData = [
                'internal_order_id' => $internalOrderId,
                'user_id' => $userId,
                'amount' => $amount,
                'currency' => $currency,
                'payment_method' => $paymentMethod,
                'details' => $details
            ];

            if ($receipt !== null) {
                $requestData['receipt'] = $receipt;
            }

            Log::channel('ms_payment_integration')->info('MsPayment: отправка запроса на создание PayIn', [
                'internal_order_id' => $internalOrderId,
                'user_id' => $userId,
                'amount' => $amount,
                'currency' => $currency,
                'payment_method' => $paymentMethod,
                'details' => $details,
                'base_uri' => $this->config['base_uri'] ?? 'unknown',
                'request_url' => self::PAYMENT_URI_PREFIX . '/payin'
            ]);

            $response = $this->client->post(self::PAYMENT_URI_PREFIX . '/payin', [
                'json' => $requestData
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            Log::channel('ms_payment_integration')->info('MsPayment: ответ на создание PayIn', [
                'internal_order_id' => $internalOrderId,
                'response_status' => 'success',
                'response_data' => $data
            ]);

            return [
                'success' => true,
                'data' => $data['data'] ?? $data
            ];
        } catch (GuzzleException $e) {
            Log::channel('ms_payment_integration')->error('MsPayment: ошибка создания PayIn', [
                'internal_order_id' => $internalOrderId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'exception_type' => 'GuzzleException'
            ]);

            return [
                'success' => false,
                'error' => 'PAYMENT_SERVICE_ERROR',
                'details' => $e->getMessage()
            ];
        } catch (\Exception $e) {
            Log::channel('ms_payment_integration')->error('MsPayment: неожиданная ошибка при создании PayIn', [
                'internal_order_id' => $internalOrderId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'exception_type' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => 'UNEXPECTED_ERROR',
                'details' => $e->getMessage()
            ];
        }
    }
}
