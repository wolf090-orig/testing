<?php

namespace app\classes\Logging;

use Psr\Log\LoggerInterface;
use support\Log;

/**
 * Обертка над логгером для автоматического добавления execution_id
 */
class LoggerWithExecutionId implements LoggerInterface
{
    private LoggerInterface $logger;
    private ExecutionIdProcessor $processor;

    public function __construct(string $channel, ?string $executionId = null)
    {
        $this->logger = Log::channel($channel);
        $this->processor = new ExecutionIdProcessor($executionId);

        // Добавляем процессор к логгеру
        if (method_exists($this->logger, 'pushProcessor')) {
            $this->logger->pushProcessor($this->processor);
        }
    }

    /**
     * Устанавливает execution_id для всех последующих логов
     */
    public function setExecutionId(?string $executionId): void
    {
        $this->processor->setExecutionId($executionId);
    }

    public function emergency($message, array $context = []): void
    {
        $this->logger->emergency($message, $context);
    }

    public function alert($message, array $context = []): void
    {
        $this->logger->alert($message, $context);
    }

    public function critical($message, array $context = []): void
    {
        $this->logger->critical($message, $context);
    }

    public function error($message, array $context = []): void
    {
        $this->logger->error($message, $context);
    }

    public function warning($message, array $context = []): void
    {
        $this->logger->warning($message, $context);
    }

    public function notice($message, array $context = []): void
    {
        $this->logger->notice($message, $context);
    }

    public function info($message, array $context = []): void
    {
        $this->logger->info($message, $context);
    }

    public function debug($message, array $context = []): void
    {
        $this->logger->debug($message, $context);
    }

    public function log($level, $message, array $context = []): void
    {
        $this->logger->log($level, $message, $context);
    }
}
