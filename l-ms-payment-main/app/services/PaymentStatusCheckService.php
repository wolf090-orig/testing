<?php

namespace App\services;

use app\interfaces\PaymentTransactionRepositoryInterface;
use app\interfaces\PaymentCallbackRepositoryInterface;
use app\interfaces\PaymentGatewayInterface;
use Psr\Log\LoggerInterface;
use support\Container;
use Exception;

/**
 * Сервис для проверки статусов платежей в FPGate
 * Содержит бизнес-логику опроса статусов и обработки ответов
 */
class PaymentStatusCheckService
{
    private PaymentTransactionRepositoryInterface $transactionRepository;
    private PaymentCallbackRepositoryInterface $callbackRepository;
    private PaymentGatewayInterface $paymentGateway;
    private LoggerInterface $logger;

    public function __construct()
    {
        $this->transactionRepository = Container::get(PaymentTransactionRepositoryInterface::class);
        $this->callbackRepository = Container::get(PaymentCallbackRepositoryInterface::class);
        $this->paymentGateway = Container::get(PaymentGatewayInterface::class);
        $this->logger = \support\Log::channel('payment_status_check');
    }

    /**
     * Проверка статусов незавершенных транзакций
     */
    public function checkPendingTransactions(int $batchSize = 1): array
    {
        $this->logger->info('Начинаем проверку статусов транзакций', [
            'batch_size' => $batchSize
        ]);

        // Получаем транзакции для проверки
        $transactions = $this->transactionRepository->getTransactionsForStatusCheck($batchSize);
        
        if (empty($transactions)) {
            $this->logger->info('Транзакции для проверки статуса не найдены');
            return ['checked' => 0, 'updated' => 0, 'errors' => 0];
        }

        $this->logger->info('Найдено транзакций для проверки статуса', ['count' => count($transactions)]);

        $checkedCount = 0;
        $updatedCount = 0;
        $errorCount = 0;

        foreach ($transactions as $transaction) {
            try {
                $wasUpdated = $this->checkTransactionStatus($transaction);
                $checkedCount++;
                
                if ($wasUpdated) {
                    $updatedCount++;
                    $this->logger->info('Статус транзакции обновлен', [
                        'internal_order_id' => $transaction['internal_order_id']
                    ]);
                } else {
                    $this->logger->debug('Статус транзакции не изменился', [
                        'internal_order_id' => $transaction['internal_order_id']
                    ]);
                }
                
            } catch (Exception $e) {
                $errorCount++;
                $this->handleStatusCheckError($transaction, $e);
            }
        }

        $this->logger->info('Проверка статусов транзакций завершена', [
            'checked' => $checkedCount,
            'updated' => $updatedCount,
            'errors' => $errorCount
        ]);

        return ['checked' => $checkedCount, 'updated' => $updatedCount, 'errors' => $errorCount];
    }

    /**
     * Проверка статуса одной транзакции
     */
    private function checkTransactionStatus(array $transaction): bool
    {
        $externalTransactionId = $transaction['external_transaction_id'];
        
        if (empty($externalTransactionId)) {
            $this->logger->warning('У транзакции отсутствует external_transaction_id, пропускаем', [
                'internal_order_id' => $transaction['internal_order_id']
            ]);
            
            // Обновляем время последней проверки
            $this->transactionRepository->updateLastStatusCheck($transaction['internal_order_id']);
            return false;
        }

        $this->logger->info('Проверяем статус транзакции в FPGate', [
            'internal_order_id' => $transaction['internal_order_id'],
            'external_transaction_id' => $externalTransactionId
        ]);

        // Запрашиваем статус в FPGate
        $statusResponse = $this->paymentGateway->getTransactionStatus($externalTransactionId);
        
        // Обновляем время последней проверки
        $this->transactionRepository->updateLastStatusCheck($transaction['internal_order_id']);
        
        // Анализируем ответ
        return $this->processStatusResponse($transaction, $statusResponse);
    }

    /**
     * Обработка ответа от FPGate
     */
    private function processStatusResponse(array $transaction, array $statusResponse): bool
    {
        $this->logger->info('Получен ответ о статусе от FPGate', [
            'internal_order_id' => $transaction['internal_order_id'],
            'response' => $statusResponse
        ]);

        $currentStatus = $statusResponse['status']['type'] ?? null;
        
        if (!$currentStatus) {
            $this->logger->warning('Отсутствует статус в ответе FPGate', [
                'internal_order_id' => $transaction['internal_order_id'],
                'response' => $statusResponse
            ]);
            return false;
        }

        // Если статус SUCCESS - обрабатываем как успешный платеж
        if (strtolower($currentStatus) === 'success') {
            return $this->handleSuccessfulPayment($transaction, $statusResponse);
        }

        // Если статус ERROR или CANCELLED - обрабатываем как неуспешный
        if (in_array(strtolower($currentStatus), ['error', 'cancelled'])) {
            return $this->handleFailedPayment($transaction, $statusResponse);
        }

        // Для остальных статусов просто логируем
        $this->logger->info('Транзакция еще в процессе', [
            'internal_order_id' => $transaction['internal_order_id'],
            'current_status' => $currentStatus
        ]);

        return false;
    }

    /**
     * Обработка успешного платежа
     */
    private function handleSuccessfulPayment(array $transaction, array $statusResponse): bool
    {
        $internalOrderId = $transaction['internal_order_id'];
        
        $this->logger->info('Платеж успешно завершен', [
            'internal_order_id' => $internalOrderId
        ]);

        // Помечаем платеж как завершенный
        $this->transactionRepository->markPaymentCompleted($internalOrderId);

        // Создаем callback для отправки в Kafka
        $this->createSuccessCallback($transaction, $statusResponse);

        return true;
    }

    /**
     * Обработка неуспешного платежа
     */
    private function handleFailedPayment(array $transaction, array $statusResponse): bool
    {
        $internalOrderId = $transaction['internal_order_id'];
        $status = $statusResponse['status']['type'] ?? 'error';
        
        $this->logger->info('Платеж завершен неуспешно', [
            'internal_order_id' => $internalOrderId,
            'final_status' => $status
        ]);

        // Помечаем платеж как завершенный (неуспешно)
        $this->transactionRepository->markPaymentCompleted($internalOrderId);

        // Создаем callback для неуспешного платежа
        $this->createFailureCallback($transaction, $statusResponse);

        return true;
    }

    /**
     * Создание callback'а для успешного платежа
     */
    private function createSuccessCallback(array $transaction, array $statusResponse): void
    {
        $callbackData = $this->prepareCallbackData($transaction, $statusResponse, 'success');
        
        $callback = [
            'external_transaction_id' => $transaction['external_transaction_id'],
            'order_id' => $transaction['internal_order_id'],
            'amount' => $transaction['amount'],
            'currency' => $transaction['currency'],
            'recalculated' => false,
            'status_type' => 'success',
            'callback_timestamp' => date('Y-m-d H:i:s'),
            'callback_data' => json_encode($callbackData)
        ];

        $this->callbackRepository->saveCallback($callback);

        $this->logger->info('Создан успешный callback из проверки статуса', [
            'internal_order_id' => $transaction['internal_order_id']
        ]);
    }

    /**
     * Создание callback'а для неуспешного платежа
     */
    private function createFailureCallback(array $transaction, array $statusResponse): void
    {
        $status = $statusResponse['status']['type'] ?? 'error';
        $callbackData = $this->prepareCallbackData($transaction, $statusResponse, $status);
        
        $callback = [
            'external_transaction_id' => $transaction['external_transaction_id'],
            'order_id' => $transaction['internal_order_id'],
            'amount' => $transaction['amount'],
            'currency' => $transaction['currency'],
            'recalculated' => false,
            'status_type' => $status,
            'callback_timestamp' => date('Y-m-d H:i:s'),
            'callback_data' => json_encode($callbackData)
        ];

        $this->callbackRepository->saveCallback($callback);

        $this->logger->info('Создан неуспешный callback из проверки статуса', [
            'internal_order_id' => $transaction['internal_order_id'],
            'status' => $status
        ]);
    }

    /**
     * Подготовка данных callback'а
     */
    private function prepareCallbackData(array $transaction, array $statusResponse, string $status): array
    {
        $attemptNumber = max(0, $transaction['gateway_request_attempts'] - 1);
        
        return [
            'token' => 'status_check_' . time(),
            'transaction_id' => $transaction['external_transaction_id'],
            'order_id' => $transaction['internal_order_id'] . '_' . $attemptNumber,
            'amount' => [
                'value' => number_format($transaction['amount'], 2, '.', ''),
                'currency' => $transaction['currency']
            ],
            'recalculated' => false,
            'timestamp' => date('Y-m-d H:i:s'),
            'status' => [
                'type' => $status,
                'error_code' => $statusResponse['status']['error_code'] ?? null,
                'error_description' => $statusResponse['status']['error_description'] ?? null
            ],
            'signature' => 'generated_from_status_check',
            'source' => 'status_check'
        ];
    }

    /**
     * Обработка ошибок при проверке статуса
     */
    private function handleStatusCheckError(array $transaction, Exception $e): void
    {
        $this->logger->error('Ошибка проверки статуса', [
            'internal_order_id' => $transaction['internal_order_id'],
            'external_transaction_id' => $transaction['external_transaction_id'],
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        // Обновляем время последней проверки даже при ошибке
        try {
            $this->transactionRepository->updateLastStatusCheck($transaction['internal_order_id']);
        } catch (Exception $updateError) {
            $this->logger->error('Не удалось обновить timestamp последней проверки', [
                'internal_order_id' => $transaction['internal_order_id'],
                'error' => $updateError->getMessage()
            ]);
        }
    }
}
