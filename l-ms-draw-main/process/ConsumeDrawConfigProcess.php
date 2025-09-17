<?php

namespace process;

use app\command\ConsumeDrawConfigCommand;
use Closure;
use support\Log;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Workerman\Timer;

/**
 * Процесс автоматического запуска консьюмера конфигурации розыгрышей
 * Постоянно работающий процесс для получения конфигурации из Kafka
 */
class ConsumeDrawConfigProcess
{
    private const string PROCESS_LOG_CHANNEL = 'process_consume_draw_config';

    public function onWorkerStart(): void
    {
        $logger = Log::channel(self::PROCESS_LOG_CHANNEL);
        $logger->info('Процесс консьюмера конфигурации розыгрышей инициализирован', [
            'description' => 'Постоянно работающий процесс для получения конфигурации из Kafka'
        ]);

        // Запуск консьюмера через 5 секунд после старта для инициализации
        Timer::add(5, $this->startConsumeDrawConfigCommand(), [], false);
    }

    /**
     * Запускает консьюмер конфигурации розыгрышей
     */
    private function startConsumeDrawConfigCommand(): Closure
    {
        return function () {
            $logger = Log::channel(self::PROCESS_LOG_CHANNEL);
            $startTime = microtime(true);

            $logger->info('🏆 Запуск консьюмера конфигурации розыгрышей', [
                'timestamp' => date('Y-m-d H:i:s'),
                'process_pid' => getmypid()
            ]);

            try {
                // Прямое выполнение команды без Application
                $command = new ConsumeDrawConfigCommand();

                // Подготавливаем ввод и вывод
                $input = new ArrayInput([]);
                $output = new BufferedOutput();

                // Выполняем команду напрямую
                $exitCode = $command->run($input, $output);
                $commandOutput = $output->fetch();

                $executionTime = round((microtime(true) - $startTime) * 1000, 2);

                if ($exitCode === 0) {
                    $logger->info('✅ Консьюмер конфигурации розыгрышей завершен успешно', [
                        'exit_code' => $exitCode,
                        'execution_time_ms' => $executionTime,
                        'output' => trim($commandOutput) ?: 'Нет вывода'
                    ]);
                } else {
                    $logger->error('❌ Консьюмер конфигурации розыгрышей завершился с ошибкой', [
                        'exit_code' => $exitCode,
                        'execution_time_ms' => $executionTime,
                        'output' => trim($commandOutput) ?: 'Нет вывода'
                    ]);
                }

                // Перезапуск через 10 секунд в случае завершения
                Timer::add(10, $this->startConsumeDrawConfigCommand(), [], false);

                $logger->info('🔄 Запланирован перезапуск консьюмера конфигурации через 10 секунд');
            } catch (\Exception $e) {
                $executionTime = round((microtime(true) - $startTime) * 1000, 2);

                $logger->error('💥 Критическая ошибка в консьюмере конфигурации', [
                    'error_message' => $e->getMessage(),
                    'error_file' => $e->getFile(),
                    'error_line' => $e->getLine(),
                    'execution_time_ms' => $executionTime,
                    'trace' => $e->getTraceAsString()
                ]);

                // Перезапуск через 30 секунд в случае ошибки
                Timer::add(30, $this->startConsumeDrawConfigCommand(), [], false);

                $logger->info('🔄 Запланирован перезапуск консьюмера конфигурации через 30 секунд после ошибки');
            }
        };
    }
} 