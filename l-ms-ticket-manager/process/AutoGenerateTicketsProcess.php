<?php

namespace process;

use app\command\LotteryTicketsCreateCommand;
use Closure;
use support\Log;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Workerman\Crontab\Crontab;

/**
 * Процесс автодогенерации билетов для безлимитных лотерей
 * Вызывает команду генерации билетов каждую минуту
 */
class AutoGenerateTicketsProcess
{
    private const string PROCESS_LOG_CHANNEL = 'process_generate_tickets';

    public function onWorkerStart(): void
    {
        $logger = Log::channel(self::PROCESS_LOG_CHANNEL);
        $logger->info('Процесс автогенерации билетов инициализирован', [
            'cron_schedule' => '* * * * *',
            'description' => 'Запуск каждую минуту для пополнения пула билетов'
        ]);

        // Запуск каждую минуту
        new Crontab('* * * * *', $this->runTicketGenerationCommand());
    }

    /**
     * Запускает команду генерации билетов напрямую
     */
    private function runTicketGenerationCommand(): Closure
    {
        return function () {
            $logger = Log::channel(self::PROCESS_LOG_CHANNEL);
            $startTime = microtime(true);

            $logger->info('🎫 Запуск автогенерации билетов', [
                'timestamp' => date('Y-m-d H:i:s'),
                'process_pid' => getmypid()
            ]);

            try {
                // Прямое выполнение команды без Application
                $command = new LotteryTicketsCreateCommand();

                // Подготавливаем ввод и вывод
                $input = new ArrayInput([]);
                $output = new BufferedOutput();

                // Выполняем команду напрямую
                $exitCode = $command->run($input, $output);
                $commandOutput = $output->fetch();

                $executionTime = round((microtime(true) - $startTime) * 1000, 2);

                if ($exitCode === 0) {
                    $logger->info('✅ Команда автогенерации билетов выполнена успешно', [
                        'exit_code' => $exitCode,
                        'execution_time_ms' => $executionTime,
                        'output' => trim($commandOutput) ?: 'Нет вывода'
                    ]);
                } else {
                    $logger->error('❌ Команда автогенерации билетов завершилась с ошибкой', [
                        'exit_code' => $exitCode,
                        'execution_time_ms' => $executionTime,
                        'output' => trim($commandOutput) ?: 'Нет вывода'
                    ]);
                }

                $logger->info('🏁 Процесс автогенерации билетов завершён', [
                    'exit_code' => $exitCode,
                    'total_execution_time_ms' => $executionTime
                ]);
            } catch (\Exception $e) {
                $executionTime = round((microtime(true) - $startTime) * 1000, 2);

                $logger->error('💥 Критическая ошибка при автогенерации билетов', [
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
