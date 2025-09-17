<?php

declare(strict_types=1);

namespace app\services;

use app\interfaces\PaymentTransactionRepositoryInterface;
use app\interfaces\PaymentGatewayResponseRepositoryInterface;
use app\interfaces\PaymentGatewayInterface;
use support\Container;
use support\Db;
use support\Log;
use Exception;

/**
 * Улучшенный сервис для создания платежей с кешированием и обработкой разрывов соединений
 */
class PaymentService
{
    private PaymentTransactionRepositoryInterface $transactionRepository;
    private PaymentGatewayResponseRepositoryInterface $responseRepository;
    private PaymentGatewayInterface $paymentGateway;

    public function __construct(
        PaymentTransactionRepositoryInterface $transactionRepository,
        PaymentGatewayResponseRepositoryInterface $responseRepository
    ) {
        $this->transactionRepository = $transactionRepository;
        $this->responseRepository = $responseRepository;
        $this->paymentGateway = Container::get(PaymentGatewayInterface::class);
    }

    /**
     * Создает пополнение (PayIn) с обработкой кеша и разрывов соединений
     */
    public function createPayIn(array $data): array
    {
        $internalOrderId = $data['internal_order_id'];

        Log::channel('payment_create')->info('PayIn request received', [
            'internal_order_id' => $internalOrderId,
            'user_id' => $data['user_id'],
            'amount' => $data['amount']
        ]);

        // 1. ПЕРВЫМ ДЕЛОМ проверяем существует ли заказ в нашей БД
        $existingTransaction = $this->transactionRepository->findByInternalOrderId($internalOrderId);

        if (!$existingTransaction) {
            // НОВЫЙ ЗАКАЗ - создаем запись и идем к шлюзу
            return $this->processNewPayment($data);
        } else {
            // СУЩЕСТВУЮЩИЙ ЗАКАЗ - проверяем статус и реквизиты
            return $this->processExistingPayment($existingTransaction);
        }
    }

    /**
     * Обработка нового платежа
     */
    private function processNewPayment(array $data): array
    {
        Log::channel('payment_create')->info('Processing new payment', [
            'internal_order_id' => $data['internal_order_id']
        ]);

        // Создаем новую транзакцию
        $transactionData = [
            'internal_order_id' => $data['internal_order_id'],
            'transaction_type' => 'payin',
            'user_id' => $data['user_id'],
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'payment_method' => $data['payment_method'],
        ];

        $transaction = $this->transactionRepository->createTransaction($transactionData);

        // Делаем первый запрос к шлюзу
        return $this->makeGatewayRequest($transaction, 0);
    }

    /**
     * Обработка существующего платежа
     */
    private function processExistingPayment(array $transaction): array
    {
        $internalOrderId = $transaction['internal_order_id'];

        Log::channel('payment_create')->info('Processing existing payment', [
            'internal_order_id' => $internalOrderId,
            'payment_completed' => $transaction['payment_completed']
        ]);

        // Проверяем есть ли УЖЕ успешный ответ с данными карты
        $hasValidPaymentDetails = $this->responseRepository->hasPaymentDetails($internalOrderId);

        if ($hasValidPaymentDetails) {
            // У нас есть реквизиты - возвращаем их клиенту
            $successfulResponse = $this->responseRepository->getSuccessfulResponse($internalOrderId);
            return $this->formatPaymentResponse($successfulResponse, $transaction);
        }

        // Реквизитов нет - анализируем можно ли повторить запрос
        return $this->analyzeLastResponseAndRetry($transaction);
    }

    /**
     * Анализ последнего ответа и повторная попытка при необходимости
     */
    private function analyzeLastResponseAndRetry(array $transaction): array
    {
        $internalOrderId = $transaction['internal_order_id'];

        // Получаем последний ответ от шлюза
        $lastResponse = $this->responseRepository->getLastResponse($internalOrderId);

        if (!$lastResponse) {
            // Ответов нет - делаем первый запрос
            return $this->makeGatewayRequest($transaction, 0);
        }

        $responseData = json_decode($lastResponse['response_data'], true);

        // Проверяем была ли ошибка дубликата
        if (isset($responseData['status']['error_code']) && $responseData['status']['error_code'] === '1011') {
            // Дубликат! Увеличиваем счетчик и пробуем еще раз
            $nextAttempt = $transaction['gateway_request_attempts'];
            
            Log::channel('payment_create')->info('Duplicate order detected, retrying with suffix', [
                'internal_order_id' => $internalOrderId,
                'attempt' => $nextAttempt
            ]);

            return $this->makeGatewayRequest($transaction, $nextAttempt);
        } else {
            // Другая ошибка - возвращаем последний ответ
            return $this->formatErrorResponse($responseData, $transaction);
        }
    }

    /**
     * Выполнение запроса к платежному шлюзу
     */
    private function makeGatewayRequest(array $transaction, int $attemptNumber): array
    {
        $internalOrderId = $transaction['internal_order_id'];
        $gatewayOrderId = $this->generateGatewayOrderId($internalOrderId, $attemptNumber);

        Log::channel('payment_create')->info('Making gateway request', [
            'internal_order_id' => $internalOrderId,
            'gateway_order_id' => $gatewayOrderId,
            'attempt' => $attemptNumber
        ]);

        // Подготавливаем данные для шлюза
        $gatewayData = [
            'order_id' => $gatewayOrderId,
            'amount' => [
                'value' => number_format((float)$transaction['amount'], 2, '.', ''),
                'currency' => $transaction['currency']
            ],
            'customer_id' => (string)$transaction['user_id'],
            'description' => $transaction['payment_method']
        ];

        // Операции с платежным шлюзом в транзакции
        Db::beginTransaction();
        try {
            // Делаем запрос через PaymentGatewayInterface (DI выбирает fake/real)
            $gatewayResponse = $this->paymentGateway->createPayIn($gatewayData);

            // Определяем флаги успешности
            $httpStatusCode = $gatewayResponse['success'] ? 200 : 400;
            $responseData = $gatewayResponse['data'] ?? $gatewayResponse;
            
            $isSuccessful = $this->determineIsSuccessful($httpStatusCode, $responseData);
            $hasPaymentDetails = $this->determineHasPaymentDetails($isSuccessful, $responseData);

            // Сохраняем ответ в payment_gateway_responses
            $responseRecord = [
                'internal_order_id' => $internalOrderId,
                'external_transaction_id' => $responseData['transaction_id'] ?? null,
                'http_status_code' => $httpStatusCode,
                'response_data' => $responseData,
                'is_successful' => $isSuccessful,
                'has_payment_details' => $hasPaymentDetails,
            ];

            $this->responseRepository->saveResponse($responseRecord);

            // Увеличиваем gateway_request_attempts += 1
            $this->transactionRepository->incrementGatewayAttempts($internalOrderId);

            // Если есть external_transaction_id - сохраняем
            if (!empty($responseData['transaction_id'])) {
                $this->transactionRepository->updateExternalTransactionId(
                    $internalOrderId, 
                    $responseData['transaction_id']
                );
            }

            Db::commit();

            // Возвращаем результат клиенту
            if ($isSuccessful && $hasPaymentDetails) {
                return $this->formatPaymentResponse($responseRecord, $transaction);
            } else {
                return $this->formatErrorResponse($responseData, $transaction);
            }

        } catch (Exception $e) {
            Db::rollBack();
            
            Log::channel('payment_create')->error('Gateway request failed', [
                'internal_order_id' => $internalOrderId,
                'gateway_order_id' => $gatewayOrderId,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Генерация order_id для шлюза с суффиксом
     */
    private function generateGatewayOrderId(string $internalOrderId, int $attemptNumber): string
    {
        return $internalOrderId . '_' . $attemptNumber;
    }

    /**
     * Определение флага is_successful
     */
    private function determineIsSuccessful(int $httpStatus, array $response): bool
    {
        // is_successful = true только если:
        // - HTTP статус 200 И status.type != "error"
        return ($httpStatus === 200) && 
               (!isset($response['status']['type']) || $response['status']['type'] !== 'error');
    }

    /**
     * Определение флага has_payment_details
     */
    private function determineHasPaymentDetails(bool $isSuccessful, array $response): bool
    {
        // has_payment_details = true только если:
        // - Успешный ответ И есть данные карты (НЕ redirect_url!)
        return $isSuccessful && 
               isset($response['details']['card']) && 
               !empty($response['details']['card']);
    }

    /**
     * Форматирование успешного ответа с реквизитами
     */
    private function formatPaymentResponse(array $responseRecord, array $transaction): array
    {
        $responseData = is_string($responseRecord['response_data']) 
            ? json_decode($responseRecord['response_data'], true)
            : $responseRecord['response_data'];

        Log::channel('payment_create')->info('Returning payment details', [
            'internal_order_id' => $transaction['internal_order_id'],
            'has_card_details' => isset($responseData['details']['card'])
        ]);

        return [
            'success' => true,
            'data' => [
                'internal_order_id' => $transaction['internal_order_id'],
                'external_transaction_id' => $responseData['transaction_id'] ?? null,
                'amount' => $transaction['amount'],
                'currency' => $transaction['currency'],
                'payment_method' => $transaction['payment_method'],
                'payment_details' => $responseData['details'] ?? [],
                'order_id' => $responseData['order_id'] ?? null,
                'status' => $responseData['status'] ?? null,
                'created_at' => $responseData['creating_date'] ?? null,
                'expires_at' => $responseData['expiring_date'] ?? null
            ]
        ];
    }

    /**
     * Форматирование ответа с ошибкой
     */
    private function formatErrorResponse(array $responseData, array $transaction): array
    {
        $errorCode = $responseData['status']['error_code'] ?? 'UNKNOWN_ERROR';
        $errorDescription = $responseData['status']['error_description'] ?? 'Неизвестная ошибка';

        Log::channel('payment_create')->warning('Returning error response', [
            'internal_order_id' => $transaction['internal_order_id'],
            'error_code' => $errorCode,
            'error_description' => $errorDescription
        ]);

        return [
            'success' => false,
            'error' => $errorCode,
            'details' => $errorDescription,
            'data' => [
                'internal_order_id' => $transaction['internal_order_id'],
                'external_transaction_id' => $responseData['transaction_id'] ?? null
            ]
        ];
    }
}
