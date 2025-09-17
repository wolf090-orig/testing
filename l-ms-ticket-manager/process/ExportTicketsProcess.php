<?php

namespace process;

use app\command\Tickets\Export\ExportDailyFixedTicketsCommand;
use app\command\Tickets\Export\ExportDailyDynamicTicketsCommand;
use app\command\Tickets\Export\ExportJackpotTicketsCommand;
use app\command\Tickets\Export\ExportSupertourTicketsCommand;
use app\enums\LotteryTypeEnum;
use Closure;
use support\Log;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Workerman\Crontab\Crontab;

/**
 * Процесс автоматического экспорта билетов всех типов лотерей
 * Запускается каждые 2 минуты для экспорта новых билетов в Kafka
 */
class ExportTicketsProcess
{
    private const string PROCESS_LOG_CHANNEL = 'process_export_tickets';

    private array $exportCommands = [
        LotteryTypeEnum::DAILY_FIXED => ExportDailyFixedTicketsCommand::class,
        LotteryTypeEnum::DAILY_DYNAMIC => ExportDailyDynamicTicketsCommand::class,
        LotteryTypeEnum::JACKPOT => ExportJackpotTicketsCommand::class,
        LotteryTypeEnum::SUPERTOUR => ExportSupertourTicketsCommand::class,
    ];

    public function onWorkerStart(): void
    {
        $logger = Log::channel(self::PROCESS_LOG_CHANNEL);
        $logger->info('Процесс экспорта билетов инициализирован', [
            'cron_schedule' => '*/2 * * * *',
            'description' => 'Запуск каждые 2 минуты для экспорта билетов всех типов лотерей',
            'lottery_types' => array_keys($this->exportCommands)
        ]);

        // Запуск каждые 2 минуты
        new Crontab('*/2 * * * *', $this->runExportTicketsCommands());
    }

    /**
     * Запускает команды экспорта билетов для всех типов лотерей
     */
    private function runExportTicketsCommands(): Closure
    {
        return function () {
            $logger = Log::channel(self::PROCESS_LOG_CHANNEL);
            $startTime = microtime(true);

            $logger->info('🎫 Запуск экспорта билетов для всех типов лотерей', [
                'timestamp' => date('Y-m-d H:i:s'),
                'process_pid' => getmypid(),
                'lottery_types_count' => count($this->exportCommands)
            ]);

            $totalExported = 0;
            $successfulExports = 0;
            $failedExports = 0;

            foreach ($this->exportCommands as $lotteryType => $commandClass) {
                try {
                    $commandStartTime = microtime(true);

                    $logger->info("🔄 Экспорт билетов типа: {$lotteryType}", [
                        'lottery_type' => $lotteryType,
                        'command_class' => $commandClass
                    ]);

                    // Создаем и выполняем команду
                    $command = new $commandClass();
                    $input = new ArrayInput([]);
                    $output = new BufferedOutput();

                    $exitCode = $command->run($input, $output);
                    $commandOutput = $output->fetch();
                    $commandExecutionTime = round((microtime(true) - $commandStartTime) * 1000, 2);

                    if ($exitCode === 0) {
                        $successfulExports++;

                        // Извлекаем количество экспортированных билетов из вывода
                        $exportedCount = $this->extractExportedTicketsCount($commandOutput);
                        $totalExported += $exportedCount;

                        $logger->info("✅ Экспорт {$lotteryType} выполнен успешно", [
                            'lottery_type' => $lotteryType,
                            'exit_code' => $exitCode,
                            'execution_time_ms' => $commandExecutionTime,
                            'exported_tickets' => $exportedCount,
                            'output' => trim($commandOutput) ?: 'Нет вывода'
                        ]);
                    } else {
                        $failedExports++;

                        $logger->error("❌ Экспорт {$lotteryType} завершился с ошибкой", [
                            'lottery_type' => $lotteryType,
                            'exit_code' => $exitCode,
                            'execution_time_ms' => $commandExecutionTime,
                            'output' => trim($commandOutput) ?: 'Нет вывода'
                        ]);
                    }
                } catch (\Exception $e) {
                    $failedExports++;
                    $commandExecutionTime = round((microtime(true) - $commandStartTime) * 1000, 2);

                    $logger->error("💥 Критическая ошибка при экспорте {$lotteryType}", [
                        'lottery_type' => $lotteryType,
                        'error_message' => $e->getMessage(),
                        'error_file' => $e->getFile(),
                        'error_line' => $e->getLine(),
                        'execution_time_ms' => $commandExecutionTime
                    ]);
                }
            }

            $totalExecutionTime = round((microtime(true) - $startTime) * 1000, 2);

            $logger->info('🏁 Процесс экспорта билетов завершён', [
                'total_execution_time_ms' => $totalExecutionTime,
                'total_exported_tickets' => $totalExported,
                'successful_exports' => $successfulExports,
                'failed_exports' => $failedExports,
                'lottery_types_processed' => count($this->exportCommands)
            ]);
        };
    }

    /**
     * Извлекает количество экспортированных билетов из вывода команды
     */
    private function extractExportedTicketsCount(string $output): int
    {
        // Ищем паттерн "Получено билетов: X" в JSON логах
        if (preg_match('/"msg":"Получено билетов: (\d+)"/', $output, $matches)) {
            return (int)$matches[1];
        }

        return 0;
    }
}
