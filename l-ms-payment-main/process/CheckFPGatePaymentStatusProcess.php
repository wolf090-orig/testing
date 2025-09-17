<?php

namespace process;

use app\command\Payments\CheckFPGateStatusCommand;
use Closure;
use support\Log;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Workerman\Crontab\Crontab;

/**
 * Процесс проверки статусов платежей FPGate
 * Запускается каждые 5 минут для проверки статусов незавершенных платежей
 */
class CheckFPGatePaymentStatusProcess
{
    private const string PROCESS_LOG_CHANNEL = 'process_fpgate_status_check';

    public function onWorkerStart(): void
    {
        $logger = Log::channel(self::PROCESS_LOG_CHANNEL);
        $logger->info('Процесс проверки статусов FPGate инициализирован', [
            'cron_schedule' => '*/5 * * * *',
            'description' => 'Запуск каждые 5 минут для проверки статусов незавершенных платежей'
        ]);

        // Запуск каждые 5 минут
        new Crontab('*/5 * * * *', $this->runCheckFPGateStatusCommand());
    }

    /**
     * Запускает команду проверки статусов FPGate
     */
    private function runCheckFPGateStatusCommand(): Closure
    {
        return function () {
            $logger = Log::channel(self::PROCESS_LOG_CHANNEL);
            $logger->info('Запуск команды проверки статусов FPGate');

            try {
                $command = new CheckFPGateStatusCommand();
                $input = new ArrayInput([]);
                $output = new BufferedOutput();

                $exitCode = $command->run($input, $output);
                $commandOutput = $output->fetch();

                if ($exitCode === 0) {
                    $logger->info('Проверка статусов FPGate завершена успешно');
                } else {
                    $logger->error('Проверка статусов FPGate завершилась с ошибкой', [
                        'exit_code' => $exitCode,
                        'output' => trim($commandOutput)
                    ]);
                }

            } catch (\Exception $e) {
                $logger->error('Критическая ошибка в проверке статусов FPGate', [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);
            }
        };
    }
}
