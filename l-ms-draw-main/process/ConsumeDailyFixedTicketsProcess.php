<?php

namespace process;

use app\command\ConsumeDailyFixedTicketsCommand;
use support\Log;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Workerman\Timer;

/**
 * Процесс консьюмера билетов daily_fixed лотерей
 */
class ConsumeDailyFixedTicketsProcess
{
    private const string PROCESS_LOG_CHANNEL = 'process_consume_daily_fixed_tickets';

    public function onWorkerStart(): void
    {
        $logger = Log::channel(self::PROCESS_LOG_CHANNEL);
        $logger->info('Процесс консьюмера daily_fixed билетов инициализирован');

        // Запуск консьюмера через 2 секунды
        Timer::add(2, function () use ($logger) {
            $this->startConsumer($logger);
        }, [], false);
    }

    private function startConsumer($logger): void
    {
        $logger->info('🎟️ Запуск консьюмера daily_fixed билетов');

        try {
            $command = new ConsumeDailyFixedTicketsCommand();
            $input = new ArrayInput([]);
            $output = new BufferedOutput();

            $exitCode = $command->run($input, $output);
            $commandOutput = $output->fetch();

            if ($exitCode === 0) {
                $logger->info('✅ Консьюмер daily_fixed завершен успешно');
            } else {
                $logger->error('❌ Консьюмер daily_fixed завершился с ошибкой', [
                    'exit_code' => $exitCode,
                    'output' => trim($commandOutput)
                ]);
            }

            // Перезапуск через 10 секунд
            Timer::add(10, function () use ($logger) {
                $this->startConsumer($logger);
            }, [], false);

        } catch (\Exception $e) {
            $logger->error('💥 Критическая ошибка в консьюмере daily_fixed', [
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