<?php

namespace app\command;

use app\model\LotteryNumber;
use app\services\LotteryGeneratorService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use support\Log;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LotteryTicketsCreateCommand extends Command
{
    /**
     * Канал логирования для генерации билетов
     */
    public const string LOG_CHANNEL = 'command_lottery_tickets_generator';

    protected static string $defaultName = 'lottery-tickets:create';
    protected static string $defaultDescription = 'Генерация билетов для лотерей';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $logger = Log::channel(self::LOG_CHANNEL);
        $logger->info('Запуск команды генерации билетов');

        $startTime = microtime(true);

        $period = CarbonPeriod::create(Carbon::now()->startOfDay(), Carbon::now()->startOfDay());
        if ($input->hasArgument('start') && $input->getArgument('start')) {
            $start = Carbon::parse($input->getArgument('start'));
            $end = $input->hasArgument('end') && $input->getArgument('end')
                ? Carbon::parse($input->getArgument('end'))
                : $start;
            $period = CarbonPeriod::create($start, $end);

            $logger->info('Установлен период генерации', [
                'start' => $start->format('Y-m-d'),
                'end' => $end->format('Y-m-d')
            ]);
        } else {
            $logger->info('Используется период по умолчанию: сегодня');
        }

        $service = new LotteryGeneratorService();
        $processedCount = 0;
        $errorCount = 0;

        try {
            LotteryNumber::whereDate('start_date', '>=', $period->start->toDate())
                ->whereDate('start_date', '<=', $period->end->toDate())
                ->where('is_tickets_generation_completed', false)
                ->chunk(1, function ($lotteries) use ($service, $logger, &$processedCount, &$errorCount) {
                    foreach ($lotteries as $lottery) {
                        try {
                            $logger->info("Начинаем генерацию билетов для лотереи", [
                                'lottery_id' => $lottery->id,
                                'lottery_name' => $lottery->lottery_name
                            ]);

                            $service->setUpLottery($lottery);

                            // Проверяем нужно ли завершить генерацию для этой лотереи
                            $this->checkAndCompleteGeneration($lottery, $logger);

                            $processedCount++;

                            $logger->info("Билеты успешно сгенерированы", [
                                'lottery_id' => $lottery->id,
                                'lottery_name' => $lottery->lottery_name
                            ]);
                        } catch (\Exception $e) {
                            $errorCount++;
                            $logger->error('Ошибка при генерации билетов', [
                                'lottery_id' => $lottery->id,
                                'lottery_name' => $lottery->lottery_name,
                                'error' => $e->getMessage(),
                                'trace' => $e->getTraceAsString()
                            ]);
                        }
                    }
                });

            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            $logger->info('Команда генерации билетов завершена', [
                'processed_count' => $processedCount,
                'error_count' => $errorCount,
                'execution_time_ms' => $executionTime
            ]);

            $output->writeln("Команда завершена: Обработано: {$processedCount}, ошибок: {$errorCount}, время: {$executionTime}ms");
        } catch (\Exception $e) {
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            $logger->error('Критическая ошибка при генерации билетов', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'execution_time_ms' => $executionTime
            ]);

            $output->writeln("Команда завершена: Критическая ошибка: {$e->getMessage()}, время: {$executionTime}ms");
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Проверяет нужно ли завершить генерацию билетов для лотереи
     */
    private function checkAndCompleteGeneration(LotteryNumber $lottery, $logger): void
    {
        // Если у типа лотереи есть max_tickets_count - значит все билеты созданы
        if (!is_null($lottery->type->max_tickets_count)) {
            $lottery->update(['is_tickets_generation_completed' => true]);

            $logger->info('Генерация билетов завершена для лотереи с лимитом', [
                'lottery_id' => $lottery->id,
                'max_tickets' => $lottery->type->max_tickets_count
            ]);
        }
        // Для безлимитных лотерей оставляем флаг false для автодогенерации
    }

    protected function configure(): void
    {
        $this->setName('lottery-tickets:create')
            ->setDescription('Генерация билетов для лотерей')
            ->addArgument('start', InputArgument::OPTIONAL, 'Начальная дата периода: 2024-12-30')
            ->addArgument('end', InputArgument::OPTIONAL, 'Конечная дата периода: 2024-12-30');
    }
}
