<?php

namespace process;

use app\command\LotteryNumbersGenerateCommand;
use Closure;
use support\Log;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Workerman\Crontab\Crontab;

/**
 * Процесс автогенерации лотерей
 * Запускается ежедневно в 00:00 для генерации лотерей на следующие 3 дня
 */
class GenerateLotteryNumbersProcess
{
    private const string PROCESS_LOG_CHANNEL = 'process_generate_lottery_numbers';

    public function onWorkerStart()
    {
        $logger = Log::channel(self::PROCESS_LOG_CHANNEL);
        $logger->info('Процесс генерации лотерей инициализирован', [
            'cron_schedule' => '0 0 * * *',
            'description' => 'Запуск ежедневно в 00:00 для генерации лотерей на следующие 3 дня'
        ]);

        // Запуск ежедневно в 00:00 для генерации лотерей на следующие 3 дня
        new Crontab('0 0 * * *', $this->generateLotteries());
    }

    /**
     * Запускает команду генерации лотерей на 3 дня вперед напрямую
     */
    private function generateLotteries(): Closure
    {
        return function () {
            $logger = Log::channel(self::PROCESS_LOG_CHANNEL);
            $startTime = microtime(true);

            $logger->info('🎰 Запуск автогенерации лотерей', [
                'timestamp' => date('Y-m-d H:i:s'),
                'process_pid' => getmypid(),
                'target_days' => 3
            ]);

            try {
                // Прямое выполнение команды без Application
                $command = new LotteryNumbersGenerateCommand();

                // Подготавливаем ввод и вывод
                $input = new ArrayInput([]);
                $output = new BufferedOutput();

                // Выполняем команду напрямую
                $exitCode = $command->run($input, $output);
                $commandOutput = $output->fetch();

                $executionTime = round((microtime(true) - $startTime) * 1000, 2);

                if ($exitCode === 0) {
                    $logger->info('✅ Команда автогенерации лотерей выполнена успешно', [
                        'exit_code' => $exitCode,
                        'execution_time_ms' => $executionTime,
                        'output' => trim($commandOutput) ?: 'Нет вывода'
                    ]);
                } else {
                    $logger->error('❌ Команда автогенерации лотерей завершилась с ошибкой', [
                        'exit_code' => $exitCode,
                        'execution_time_ms' => $executionTime,
                        'output' => trim($commandOutput) ?: 'Нет вывода'
                    ]);
                }

                $logger->info('🏁 Процесс автогенерации лотерей завершён', [
                    'exit_code' => $exitCode,
                    'total_execution_time_ms' => $executionTime
                ]);
            } catch (\Exception $e) {
                $executionTime = round((microtime(true) - $startTime) * 1000, 2);

                $logger->error('💥 Критическая ошибка при генерации лотерей', [
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
