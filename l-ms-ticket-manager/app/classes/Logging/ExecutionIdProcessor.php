<?php

namespace app\classes\Logging;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

/**
 * Процессор для автоматического добавления execution_id в логи
 */
class ExecutionIdProcessor implements ProcessorInterface
{
    private ?string $executionId = null;

    public function __construct(?string $executionId = null)
    {
        $this->executionId = $executionId;
    }

    /**
     * Устанавливает execution_id для всех последующих логов
     */
    public function setExecutionId(?string $executionId): void
    {
        $this->executionId = $executionId;
    }

    /**
     * Добавляет execution_id к логу, если он установлен
     */
    public function __invoke(LogRecord $record): LogRecord
    {
        if ($this->executionId !== null) {
            $record->extra['execution_id'] = $this->executionId;
        }

        return $record;
    }
}
