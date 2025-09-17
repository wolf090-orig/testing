<?php

namespace process;

use app\command\Payments\ProcessCallbacksCommand;
use Closure;
use support\Log;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Workerman\Crontab\Crontab;

/**
 * Процесс обработки payment callback'ов
 * Запускается каждую минуту для обработки необработанных callback'ов
 */
class ProcessPaymentCallbacksProcess
{
    private const string PROCESS_LOG_CHANNEL = 'process_payment_callbacks';

    public function onWorkerStart(): void
    {
        $logger = Log::channel(self::PROCESS_LOG_CHANNEL);
        $logger->info('Процесс обработки payment callback\'ов инициализирован', [
            'cron_schedule' => '* * * * *',
            'description' => 'Запуск каждую минуту для обработки необработанных callback\'ов'
        ]);

        // Запуск каждую минуту
        new Crontab('* * * * *', $this->runProcessCallbacksCommand());
    }

    /**
     * Запускает команду обработки callback'ов
     */
    private function runProcessCallbacksCommand(): Closure
    {
        return function () {
            $logger = Log::channel(self::PROCESS_LOG_CHANNEL);
            $logger->info('Запуск команды обработки callback\'ов');

            try {
                $command = new ProcessCallbacksCommand();
                $input = new ArrayInput([]);
                $output = new BufferedOutput();

                $exitCode = $command->run($input, $output);
                $commandOutput = $output->fetch();

                if ($exitCode === 0) {
                    $logger->info('Обработка callback\'ов завершена успешно');
                } else {
                    $logger->error('Обработка callback\'ов завершилась с ошибкой', [
                        'exit_code' => $exitCode,
                        'output' => trim($commandOutput)
                    ]);
                }

            } catch (\Exception $e) {
                $logger->error('Критическая ошибка в обработке callback\'ов', [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);
            }
        };
    }
}
