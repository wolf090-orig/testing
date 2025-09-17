<?php

namespace process;

use app\command\ExportLotteryWinnersConfigCommand;
use Closure;
use support\Log;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Workerman\Crontab\Crontab;

/**
 * Процесс автоматического экспорта конфигурации победителей для готовых к розыгрышу лотерей
 * Запускается каждую минуту для отправки данных в ms-draw-service через Kafka
 */
class ExportLotteryWinnersConfigProcess
{
    private const string PROCESS_LOG_CHANNEL = 'process_export_lottery_winners_config';

    public function onWorkerStart(): void
    {
        $logger = Log::channel(self::PROCESS_LOG_CHANNEL);
        $logger->info('Процесс экспорта конфигурации победителей инициализирован', [
            'cron_schedule' => '* * * * *',
            'description' => 'Запуск каждую минуту для экспорта конфигурации лотерей с закрытыми продажами'
        ]);

        // Запуск каждую минуту
        new Crontab('* * * * *', $this->runExportLotteryWinnersConfigCommand());
    }

    /**
     * Запускает команду экспорта конфигурации победителей
     */
    private function runExportLotteryWinnersConfigCommand(): Closure
    {
        return function () {
            $logger = Log::channel(self::PROCESS_LOG_CHANNEL);
            $startTime = microtime(true);

            $logger->info('🏆 Запуск экспорта конфигурации победителей', [
                'timestamp' => date('Y-m-d H:i:s'),
                'process_pid' => getmypid()
            ]);

            try {
                // Создаем и выполняем команду
                $command = new ExportLotteryWinnersConfigCommand();
                $input = new ArrayInput([]);
                $output = new BufferedOutput();

                $exitCode = $command->run($input, $output);
                $commandOutput = $output->fetch();
                $executionTime = round((microtime(true) - $startTime) * 1000, 2);

                if ($exitCode === 0) {
                    $logger->info('✅ Команда экспорта конфигурации победителей выполнена успешно', [
                        'exit_code' => $exitCode,
                        'execution_time_ms' => $executionTime,
                        'output' => trim($commandOutput) ?: 'Нет вывода'
                    ]);
                } else {
                    $logger->error('❌ Команда экспорта конфигурации победителей завершилась с ошибкой', [
                        'exit_code' => $exitCode,
                        'execution_time_ms' => $executionTime,
                        'output' => trim($commandOutput) ?: 'Нет вывода'
                    ]);
                }

                $logger->info('🏁 Процесс экспорта конфигурации победителей завершён', [
                    'exit_code' => $exitCode,
                    'total_execution_time_ms' => $executionTime
                ]);

            } catch (\Exception $e) {
                $executionTime = round((microtime(true) - $startTime) * 1000, 2);

                $logger->error('💥 Критическая ошибка процесса экспорта конфигурации победителей', [
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