<?php

namespace app\services;

use app\libraries\kafka\messages\KafkaProducerMessage;
use app\libraries\kafka\producers\Producer;
use Carbon\Carbon;
use Exception;
use Psr\Log\LoggerInterface;
use support\Log;

/**
 * Сервис для обработки Dead Letter Queue (DLQ)
 * Отправляет неудачные сообщения в специальные топики для ручной обработки
 */
class DlqService
{
    private LoggerInterface $logger;
    
    public function __construct()
    {
        $this->logger = Log::channel('default');
    }

    /**
     * Отправить сообщение в DLQ топик
     */
    public function sendToDlq(string $originalTopic, array $messageBody, array $headers, Exception $exception): void
    {
        try {
            $dlqTopic = $this->getDlqTopicForOriginal($originalTopic);
            
            // Обогащаем сообщение метаданными об ошибке
            $dlqMessage = [
                'original_topic' => $originalTopic,
                'original_body' => $messageBody,
                'original_headers' => $headers,
                'error_message' => $exception->getMessage(),
                'error_type' => get_class($exception),
                'error_file' => $exception->getFile(),
                'error_line' => $exception->getLine(),
                'failed_at' => Carbon::now()->toISOString(),
                'retry_count' => $headers['retry_count'] ?? 0,
            ];

            $producer = Producer::createFromConfigKey('tickets', $dlqTopic);
            $producer->sendMessage(new KafkaProducerMessage($dlqMessage));

            $this->logger->warning('Сообщение отправлено в DLQ', [
                'original_topic' => $originalTopic,
                'dlq_topic' => $dlqTopic,
                'error' => $exception->getMessage(),
                'lottery_id' => $headers['lottery_id'] ?? $messageBody['lottery_id'] ?? null,
            ]);

        } catch (Exception $e) {
            $this->logger->error('Ошибка отправки сообщения в DLQ', [
                'original_topic' => $originalTopic,
                'error' => $e->getMessage(),
                'original_error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Определить DLQ топик для исходного топика
     */
    private function getDlqTopicForOriginal(string $originalTopic): string
    {
        $ticketTopics = [
            'daily_fixed_tickets_v1',
            'daily_dynamic_tickets_v1', 
            'jackpot_tickets_v1',
            'supertour_tickets_v1',
            // Устаревшие топики
            'daily_tickets_v1',
            'weekly_tickets_v1',
            'monthly_tickets_v1',
            'yearly_tickets_v1',
        ];

        if (in_array($originalTopic, $ticketTopics)) {
            return config('kafka.dlq_tickets_topic');
        }

        if (str_contains($originalTopic, 'schedules') || str_contains($originalTopic, 'config')) {
            return config('kafka.dlq_schedules_topic');
        }

        // Fallback для неизвестных топиков
        return config('kafka.dlq_tickets_topic');
    }

    /**
     * Проверить, нужно ли отправлять в DLQ на основе ошибки
     */
    public function shouldSendToDlq(Exception $exception): bool
    {
        $retryableErrors = [
            'Connection timeout',
            'Network error', 
            'Temporary database error',
            'Lock timeout',
            'deadlock detected',
        ];

        $errorMessage = strtolower($exception->getMessage());
        
        foreach ($retryableErrors as $retryableError) {
            if (str_contains($errorMessage, strtolower($retryableError))) {
                return false; // Можно повторить
            }
        }

        return true; // Отправить в DLQ
    }

    /**
     * Обработать сообщение с проверкой на отправку в DLQ
     */
    public function processWithDlq(string $topic, array $messageBody, array $headers, callable $processor): bool
    {
        try {
            $processor($messageBody, $headers);
            return true;
            
        } catch (Exception $e) {
            $this->logger->error('Ошибка обработки сообщения', [
                'topic' => $topic,
                'error' => $e->getMessage(),
                'lottery_id' => $headers['lottery_id'] ?? $messageBody['lottery_id'] ?? null,
            ]);

            if ($this->shouldSendToDlq($e)) {
                $this->sendToDlq($topic, $messageBody, $headers, $e);
            } else {
                $this->logger->info('Ошибка помечена как повторяемая, сообщение не отправлено в DLQ', [
                    'topic' => $topic,
                    'error' => $e->getMessage(),
                ]);
            }
            
            return false;
        }
    }

    /**
     * Получить статистику DLQ топиков
     */
    public function getDlqStats(): array
    {
        // TODO: Реализовать получение статистики через Kafka Admin API
        return [
            'dlq_tickets_messages' => 0,
            'dlq_schedules_messages' => 0,
        ];
    }
} 