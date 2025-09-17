<?php

namespace process;

use app\command\ConsumeSupertourTicketsCommand;
use support\Log;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Workerman\Timer;

/**
 * Процесс консьюмера билетов supertour лотерей
 */
class ConsumeSupertourTicketsProcess
{
    private const string PROCESS_LOG_CHANNEL = 'process_consume_supertour_tickets';

    public function onWorkerStart(): void
    {
        $logger = Log::channel(self::PROCESS_LOG_CHANNEL);
        $logger->info('Процесс консьюмера supertour билетов инициализирован');

        // Запуск консьюмера через 11 секунд
        Timer::add(11, function () use ($logger) {
            $this->startConsumer($logger);
        }, [], false);
    }

    private function startConsumer($logger): void
    {
        $logger->info('🎟️ Запуск консьюмера supertour билетов');

        try {
            $command = new ConsumeSupertourTicketsCommand();
            $input = new ArrayInput([]);
            $output = new BufferedOutput();

            $exitCode = $command->run($input, $output);
            $commandOutput = $output->fetch();

            if ($exitCode === 0) {
                $logger->info('✅ Консьюмер supertour завершен успешно');
            } else {
                $logger->error('❌ Консьюмер supertour завершился с ошибкой', [
                    'exit_code' => $exitCode,
                    'output' => trim($commandOutput)
                ]);
            }

            // Перезапуск через 10 секунд
            Timer::add(10, function () use ($logger) {
                $this->startConsumer($logger);
            }, [], false);
        } catch (\Exception $e) {
            $logger->error('💥 Критическая ошибка в консьюмере supertour', [
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
