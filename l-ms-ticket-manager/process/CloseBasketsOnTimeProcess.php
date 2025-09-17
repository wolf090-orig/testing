<?php

namespace process;

use app\command\BaskedExpiredCloseCommand;
use Closure;
use support\Log;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Workerman\Crontab\Crontab;

/**
 * Процесс автоматического закрытия истекших корзин
 * Запускается каждую минуту для закрытия корзин с истёкшими датами
 * Использует команду BaskedExpiredCloseCommand для избежания дублирования кода
 */
class CloseBasketsOnTimeProcess
{
    private const string PROCESS_LOG_CHANNEL = 'process_close_baskets';

    public function onWorkerStart()
    {
        $logger = Log::channel(self::PROCESS_LOG_CHANNEL);
        $logger->info('Процесс закрытия корзин инициализирован', [
            'cron_schedule' => '* * * * *',
            'description' => 'Запуск каждую минуту'
        ]);

        // Запуск каждую минуту для тестирования
        new Crontab('* * * * *', $this->closeExpiredBaskets());
    }

    /**
     * Вызываем команду закрытия корзин напрямую
     */
    private function closeExpiredBaskets(): Closure
    {
        return function () {
            $logger = Log::channel(self::PROCESS_LOG_CHANNEL);
            $startTime = microtime(true);

            $logger->info('🔄 Запуск процесса закрытия истекших корзин', [
                'timestamp' => date('Y-m-d H:i:s'),
                'process_pid' => getmypid()
            ]);

            try {
                // Прямое выполнение команды без Application
                $command = new BaskedExpiredCloseCommand();

                // Подготавливаем ввод и вывод
                $input = new ArrayInput([]);
                $output = new BufferedOutput();

                // Выполняем команду напрямую
                $exitCode = $command->run($input, $output);
                $commandOutput = $output->fetch();

                $executionTime = round((microtime(true) - $startTime) * 1000, 2);

                if ($exitCode === 0) {
                    $logger->info('✅ Команда закрытия корзин выполнена успешно', [
                        'exit_code' => $exitCode,
                        'execution_time_ms' => $executionTime,
                        'output' => trim($commandOutput) ?: 'Нет вывода'
                    ]);
                } else {
                    $logger->error('❌ Команда закрытия корзин завершилась с ошибкой', [
                        'exit_code' => $exitCode,
                        'execution_time_ms' => $executionTime,
                        'output' => trim($commandOutput) ?: 'Нет вывода'
                    ]);
                }

                $logger->info('🏁 Процесс закрытия корзин завершён', [
                    'exit_code' => $exitCode,
                    'total_execution_time_ms' => $executionTime
                ]);
            } catch (\Exception $e) {
                $executionTime = round((microtime(true) - $startTime) * 1000, 2);

                $logger->error('💥 Критическая ошибка в процессе закрытия корзин', [
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
