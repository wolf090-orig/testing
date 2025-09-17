<?php

namespace process;

use app\command\Tickets\Export\ExportActiveLotteriesCommand;
use Closure;
use support\Log;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Workerman\Crontab\Crontab;

/**
 * Процесс автоматического экспорта расписания активных лотерей
 * Запускается каждые 5 минут для отправки актуального расписания в Kafka
 */
class ExportActiveLotteriesProcess
{
    private const string PROCESS_LOG_CHANNEL = 'process_export_active_lotteries';

    public function onWorkerStart(): void
    {
        $logger = Log::channel(self::PROCESS_LOG_CHANNEL);
        $logger->info('Процесс экспорта активных лотерей инициализирован', [
            'cron_schedule' => '*/5 * * * *',
            'description' => 'Запуск каждые 5 минут для экспорта расписания лотерей'
        ]);

        // Запуск каждые 5 минут
        new Crontab('*/5 * * * *', $this->runExportActiveLotteriesCommand());
    }

    /**
     * Запускает команду экспорта активных лотерей напрямую
     */
    private function runExportActiveLotteriesCommand(): Closure
    {
        return function () {
            $logger = Log::channel(self::PROCESS_LOG_CHANNEL);
            $startTime = microtime(true);

            $logger->info('📅 Запуск экспорта активных лотерей', [
                'timestamp' => date('Y-m-d H:i:s'),
                'process_pid' => getmypid()
            ]);

            try {
                // Прямое выполнение команды без Application
                $command = new ExportActiveLotteriesCommand();

                // Подготавливаем ввод и вывод
                $input = new ArrayInput([]);
                $output = new BufferedOutput();

                // Выполняем команду напрямую
                $exitCode = $command->run($input, $output);
                $commandOutput = $output->fetch();

                $executionTime = round((microtime(true) - $startTime) * 1000, 2);

                if ($exitCode === 0) {
                    $logger->info('✅ Команда экспорта активных лотерей выполнена успешно', [
                        'exit_code' => $exitCode,
                        'execution_time_ms' => $executionTime,
                        'output' => trim($commandOutput) ?: 'Нет вывода'
                    ]);
                } else {
                    $logger->error('❌ Команда экспорта активных лотерей завершилась с ошибкой', [
                        'exit_code' => $exitCode,
                        'execution_time_ms' => $executionTime,
                        'output' => trim($commandOutput) ?: 'Нет вывода'
                    ]);
                }

                $logger->info('🏁 Процесс экспорта активных лотерей завершён', [
                    'exit_code' => $exitCode,
                    'total_execution_time_ms' => $executionTime
                ]);
            } catch (\Exception $e) {
                $executionTime = round((microtime(true) - $startTime) * 1000, 2);

                $logger->error('💥 Критическая ошибка при экспорте активных лотерей', [
                    'error_message' => $e->getMessage(),
                    'error_file' => $e->getFile(),
                    'error_line' => $e->getLine(),
                    'execution_time_ms' => $executionTime,
                    'trace' => $e->getTraceAsString()
                ]);
            }
        };
    }
}
