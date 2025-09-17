<?php

namespace App\services;

use app\interfaces\PaymentCallbackRepositoryInterface;
use Psr\Log\LoggerInterface;
use Exception;
use support\Container;

/**
 * Сервис для обработки payment callback'ов
 * Содержит бизнес-логику обработки callback'ов и отправки в Kafka
 */
class PaymentCallbackService
{
    private PaymentCallbackRepositoryInterface $callbackRepository;
    private LoggerInterface $logger;
    
    // Kafka конфигурация
    private string $paymentStatusTopic;
    private int $maxRetries;

    public function __construct()
    {
        $this->callbackRepository = Container::get(PaymentCallbackRepositoryInterface::class);
        $this->logger = \support\Log::channel('payment_callbacks');
        
        // Загружаем настройки из env
        $this->paymentStatusTopic = $_ENV['KAFKA_PAYMENT_STATUS_TOPIC'] ?? 'payment_status_v1';
        $this->maxRetries = (int) ($_ENV['PAYMENT_CALLBACK_MAX_RETRIES'] ?? 3);
    }

    /**
     * Обработка необработанных callback'ов
     */
    public function processUnprocessedCallbacks(int $batchSize = 100): array
    {
        $this->logger->info('Начинаем обработку callback\'ов', [
            'batch_size' => $batchSize,
            'max_retries' => $this->maxRetries
        ]);

        // Получаем необработанные callback'и
        $callbacks = $this->callbackRepository->getUnprocessedCallbacks($batchSize);
        
        if (empty($callbacks)) {
            $this->logger->info('Необработанные callback\'и не найдены');
            return ['processed' => 0, 'errors' => 0];
        }

        $this->logger->info('Найдено необработанных callback\'ов', ['count' => count($callbacks)]);

        $processedCount = 0;
        $errorCount = 0;

        foreach ($callbacks as $callback) {
            try {
                $this->processCallback($callback);
                $processedCount++;
                
                $this->logger->info('Callback успешно обработан', [
                    'callback_id' => $callback['id'],
                    'order_id' => $callback['order_id']
                ]);
                
            } catch (Exception $e) {
                $errorCount++;
                $this->handleCallbackError($callback, $e);
            }
        }

        $this->logger->info('Обработка callback\'ов завершена', [
            'processed' => $processedCount,
            'errors' => $errorCount
        ]);

        return ['processed' => $processedCount, 'errors' => $errorCount];
    }

    /**
     * Обработка одного callback'а
     */
    private function processCallback(array $callback): void
    {
        // Подготавливаем данные для Kafka
        $kafkaMessage = $this->prepareKafkaMessage($callback);
        
        // Отправляем в Kafka (все типы в один топик)
        $this->sendToKafka($this->paymentStatusTopic, $kafkaMessage);
        
        // Помечаем как обработанный
        $this->callbackRepository->markAsProcessed([$callback['id']]);
        
        $this->logger->info('Callback обработан и отправлен в Kafka', [
            'callback_id' => $callback['id'],
            'order_id' => $callback['order_id'],
            'topic' => $this->paymentStatusTopic,
            'status' => $callback['status_type']
        ]);
    }

    /**
     * Подготовка сообщения для Kafka
     */
    private function prepareKafkaMessage(array $callback): array
    {
        $callbackData = json_decode($callback['callback_data'], true);
        
        return [
            'internal_order_id' => $callback['order_id'],
            'external_transaction_id' => $callback['external_transaction_id'],
            'payment_gateway' => 'fpgate',
            'payment_status' => strtolower($callback['status_type']),
            'amount' => $callback['amount'],
            'currency' => $callback['currency'],
            'recalculated' => $callback['recalculated'],
            'callback_timestamp' => $callback['callback_timestamp'],
            'callback_id' => $callback['id'],
            'processed_at' => date('Y-m-d H:i:s'),
            
            // Дополнительные данные из callback
            'gateway_data' => [
                'token' => $callbackData['token'] ?? null,
                'signature' => $callbackData['signature'] ?? null,
                'fee' => $callbackData['amount']['fee'] ?? null,
                'details' => $callbackData['details'] ?? null
            ]
        ];
    }



    /**
     * Отправка сообщения в Kafka
     */
    private function sendToKafka(string $topic, array $message): void
    {
        // TODO: Реализовать отправку в Kafka
        // Пока заглушка - логируем что отправили бы
        
        $this->logger->info('Сообщение будет отправлено в Kafka', [
            'topic' => $topic,
            'message' => $message
        ]);
        
        // В реальной реализации:
        // $producer = app(KafkaProducerInterface::class);
        // $producer->send($topic, $message);
    }

    /**
     * Обработка ошибок при обработке callback'а
     */
    private function handleCallbackError(array $callback, Exception $e): void
    {
        // Увеличиваем счетчик попыток
        $this->callbackRepository->incrementRetryCount($callback['id']);
        
        $retryCount = $callback['retry_count'] + 1;
        
        if ($retryCount >= $this->maxRetries) {
            $this->logger->error('Превышен лимит повторных попыток обработки callback', [
                'callback_id' => $callback['id'],
                'order_id' => $callback['order_id'],
                'retry_count' => $retryCount,
                'max_retries' => $this->maxRetries,
                'error' => $e->getMessage()
            ]);
        } else {
            $this->logger->warning('Ошибка обработки callback, будет повторная попытка', [
                'callback_id' => $callback['id'],
                'order_id' => $callback['order_id'],
                'retry_count' => $retryCount,
                'max_retries' => $this->maxRetries,
                'error' => $e->getMessage()
            ]);
        }
    }
}
