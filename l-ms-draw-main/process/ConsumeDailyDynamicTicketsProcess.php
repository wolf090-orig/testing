<?php

namespace process;

use app\command\ConsumeDailyDynamicTicketsCommand;
use support\Log;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Workerman\Timer;

/**
 * Процесс консьюмера билетов daily_dynamic лотерей
 */
class ConsumeDailyDynamicTicketsProcess
{
    private const string PROCESS_LOG_CHANNEL = 'process_consume_daily_dynamic_tickets';

    public function onWorkerStart(): void
    {
        $logger = Log::channel(self::PROCESS_LOG_CHANNEL);
        $logger->info('Процесс консьюмера daily_dynamic билетов инициализирован');

        // Запуск консьюмера через 5 секунд
        Timer::add(5, function () use ($logger) {
            $this->startConsumer($logger);
        }, [], false);
    }

    private function startConsumer($logger): void
    {
        $logger->info('🎟️ Запуск консьюмера daily_dynamic билетов');

        try {
            $command = new ConsumeDailyDynamicTicketsCommand();
            $input = new ArrayInput([]);
            $output = new BufferedOutput();

            $exitCode = $command->run($input, $output);
            $commandOutput = $output->fetch();

            if ($exitCode === 0) {
                $logger->info('✅ Консьюмер daily_dynamic завершен успешно');
            } else {
                $logger->error('❌ Консьюмер daily_dynamic завершился с ошибкой', [
                    'exit_code' => $exitCode,
                    'output' => trim($commandOutput)
                ]);
            }

            // Перезапуск через 10 секунд
            Timer::add(10, function () use ($logger) {
                $this->startConsumer($logger);
            }, [], false);

        } catch (\Exception $e) {
            $logger->error('💥 Критическая ошибка в консьюмере daily_dynamic', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            // Перезапуск через 30 секунд после ошибки
            Timer::add(30, function () use ($logger) {
                $this->startConsumer($logger);
            }, [], false);
        }
    }
} 