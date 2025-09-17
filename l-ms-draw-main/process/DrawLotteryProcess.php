<?php

namespace process;

use app\command\DrawLotteriesCommand;
use Closure;
use support\Log;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Workerman\Crontab\Crontab;

/**
 * Процесс автоматического проведения розыгрышей лотерей
 * Запускается каждую минуту для проверки и проведения готовых розыгрышей
 */
class DrawLotteryProcess
{
    private const string PROCESS_LOG_CHANNEL = 'process_draw_lottery';

    public function onWorkerStart(): void
    {
        $logger = Log::channel(self::PROCESS_LOG_CHANNEL);
        $logger->info('Процесс розыгрыша лотерей инициализирован', [
            'cron_schedule' => '* * * * *',
            'description' => 'Запуск каждую минуту для проведения готовых розыгрышей'
        ]);

        // Запуск каждую минуту
        new Crontab('* * * * *', $this->runDrawLotteriesCommand());
    }

    /**
     * Запускает команду проведения розыгрышей напрямую
     */
    private function runDrawLotteriesCommand(): Closure
    {
        return function () {
            $logger = Log::channel(self::PROCESS_LOG_CHANNEL);
            $startTime = microtime(true);

            $logger->info('🎲 Запуск проведения розыгрышей лотерей', [
                'timestamp' => date('Y-m-d H:i:s'),
                'process_pid' => getmypid()
            ]);

            try {
                // Прямое выполнение команды без Application
                $command = new DrawLotteriesCommand();

                // Подготавливаем ввод и вывод
                $input = new ArrayInput([]);
                $output = new BufferedOutput();

                // Выполняем команду напрямую
                $exitCode = $command->run($input, $output);
                $commandOutput = $output->fetch();

                $executionTime = round((microtime(true) - $startTime) * 1000, 2);

                if ($exitCode === 0) {
                    $logger->info('✅ Команда проведения розыгрышей выполнена успешно', [
                        'exit_code' => $exitCode,
                        'execution_time_ms' => $executionTime,
                        'output' => trim($commandOutput) ?: 'Нет вывода'
                    ]);
                } else {
                    $logger->error('❌ Команда проведения розыгрышей завершилась с ошибкой', [
                        'exit_code' => $exitCode,
                        'execution_time_ms' => $executionTime,
                        'output' => trim($commandOutput) ?: 'Нет вывода'
                    ]);
                }

                $logger->info('🏁 Процесс проведения розыгрышей завершён', [
                    'exit_code' => $exitCode,
                    'total_execution_time_ms' => $executionTime
                ]);
            } catch (\Exception $e) {
                $executionTime = round((microtime(true) - $startTime) * 1000, 2);

                $logger->error('💥 Критическая ошибка при проведении розыгрышей', [
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