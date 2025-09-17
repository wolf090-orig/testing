<?php

namespace process;

use app\command\ConsumeSchedulesCommand;
use Closure;
use support\Log;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Workerman\Timer;

/**
 * Процесс автоматического запуска консьюмера расписаний лотерей
 * Постоянно работающий процесс для получения расписаний из Kafka
 */
class ConsumeSchedulesProcess
{
    private const string PROCESS_LOG_CHANNEL = 'process_consume_schedules';

    public function onWorkerStart(): void
    {
        $logger = Log::channel(self::PROCESS_LOG_CHANNEL);
        $logger->info('Процесс консьюмера расписаний лотерей инициализирован', [
            'description' => 'Постоянно работающий процесс для получения расписаний из Kafka'
        ]);

        // Запуск консьюмера через 5 секунд после старта для инициализации
        Timer::add(5, $this->startConsumeSchedulesCommand(), [], false);
    }

    /**
     * Запускает консьюмер расписаний лотерей
     */
    private function startConsumeSchedulesCommand(): Closure
    {
        return function () {
            $logger = Log::channel(self::PROCESS_LOG_CHANNEL);
            $startTime = microtime(true);

            $logger->info('📅 Запуск консьюмера расписаний лотерей', [
                'timestamp' => date('Y-m-d H:i:s'),
                'process_pid' => getmypid()
            ]);

            try {
                // Прямое выполнение команды без Application
                $command = new ConsumeSchedulesCommand();

                // Подготавливаем ввод и вывод
                $input = new ArrayInput([]);
                $output = new BufferedOutput();

                // Выполняем команду напрямую
                $exitCode = $command->run($input, $output);
                $commandOutput = $output->fetch();

                $executionTime = round((microtime(true) - $startTime) * 1000, 2);

                if ($exitCode === 0) {
                    $logger->info('✅ Консьюмер расписаний завершен успешно', [
                        'exit_code' => $exitCode,
                        'execution_time_ms' => $executionTime,
                        'output' => trim($commandOutput) ?: 'Нет вывода'
                    ]);
                } else {
                    $logger->error('❌ Консьюмер расписаний завершился с ошибкой', [
                        'exit_code' => $exitCode,
                        'execution_time_ms' => $executionTime,
                        'output' => trim($commandOutput) ?: 'Нет вывода'
                    ]);
                }

                // Перезапуск через 10 секунд в случае завершения
                Timer::add(10, $this->startConsumeSchedulesCommand(), [], false);

                $logger->info('🔄 Запланирован перезапуск консьюмера расписаний через 10 секунд');
            } catch (\Exception $e) {
                $executionTime = round((microtime(true) - $startTime) * 1000, 2);

                $logger->error('💥 Критическая ошибка в консьюмере расписаний', [
                    'error_message' => $e->getMessage(),
                    'error_file' => $e->getFile(),
                    'error_line' => $e->getLine(),
                    'execution_time_ms' => $executionTime,
                    'trace' => $e->getTraceAsString()
                ]);

                // Перезапуск через 30 секунд в случае ошибки
                Timer::add(30, $this->startConsumeSchedulesCommand(), [], false);

                $logger->info('🔄 Запланирован перезапуск консьюмера расписаний через 30 секунд после ошибки');
            }
        };
    }
}
