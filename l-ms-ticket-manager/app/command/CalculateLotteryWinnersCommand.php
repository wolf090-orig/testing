<?php

namespace app\command;

use app\services\LeaderBoardService;
use app\model\LotteryNumber;
use Carbon\Carbon;
use Exception;
use Psr\Log\LoggerInterface;
use support\Log;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Команда для расчета и сохранения количества победителей лотерей
 * Обрабатывает лотереи, у которых продажи закончились 2+ минуты назад
 */
class CalculateLotteryWinnersCommand extends Command
{
    protected static string $defaultName = 'calculate_lottery_winners:run';
    protected static string $defaultDescription = 'Рассчитать и сохранить количество победителей для закрытых лотерей';

    private const int MINUTES_AFTER_END = 2; // Ждем 2 минуты после закрытия продаж
    private LoggerInterface $logger;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->logger = Log::channel('command_calculate_lottery_winners');
        $startTime = microtime(true);
        $specificLotteryId = (int) $input->getOption('lottery-id');

        $this->logger->info('🧮 Запуск расчета количества победителей', [
            'specific_lottery_id' => $specificLotteryId ?: 'все подходящие',
            'minutes_after_end' => self::MINUTES_AFTER_END
        ]);

        try {
            // Находим лотереи для обработки
            $query = LotteryNumber::where('is_drawn', false)
                ->where('is_active', true)
                ->whereNotNull('prize_configuration_id')
                ->whereNull('calculated_winners_count'); // Только те, где еще не рассчитано

            if ($specificLotteryId) {
                $query->where('id', $specificLotteryId);
            } else {
                // Продажи закончились 2+ минуты назад
                $cutoffTime = Carbon::now()->subMinutes(self::MINUTES_AFTER_END);
                $query->where('end_date', '<=', $cutoffTime);
            }

            $lotteries = $query->get();

            if ($lotteries->isEmpty()) {
                $executionTime = round((microtime(true) - $startTime) * 1000, 2);
                $this->logger->info('🔍 Нет лотерей для расчета количества победителей', [
                    'execution_time_ms' => $executionTime
                ]);
                $output->writeln("Нет лотерей для обработки");
                return Command::SUCCESS;
            }

            $this->logger->info('📋 Найдены лотереи для расчета', [
                'count' => $lotteries->count(),
                'lottery_ids' => $lotteries->pluck('id')->toArray()
            ]);

            $successCount = 0;
            $errorCount = 0;

            foreach ($lotteries as $lottery) {
                try {
                    $lotteryStartTime = microtime(true);

                    // Рассчитываем количество победителей
                    $leaderBoardService = new LeaderBoardService($lottery->id);
                    $leaderBoard = $leaderBoardService->getLeaderBoard();
                    $winnersCount = count($leaderBoard['prize_details']);

                    // Сохраняем в БД
                    $lottery->calculated_winners_count = $winnersCount;
                    $lottery->save();

                    $lotteryExecutionTime = round((microtime(true) - $lotteryStartTime) * 1000, 2);
                    $successCount++;

                    $this->logger->info('✅ Количество победителей рассчитано и сохранено', [
                        'lottery_id' => $lottery->id,
                        'lottery_type' => $lottery->lotteryType->name,
                        'winners_count' => $winnersCount,
                        'participants' => $leaderBoard['players_quantity'],
                        'tickets_sold' => $leaderBoard['paid_tickets_count'],
                        'execution_time_ms' => $lotteryExecutionTime
                    ]);

                    $output->writeln("✅ Лотерея {$lottery->id}: {$winnersCount} победителей");
                } catch (Exception $e) {
                    $errorCount++;
                    $this->logger->error('❌ Ошибка расчета количества победителей', [
                        'lottery_id' => $lottery->id,
                        'error_message' => $e->getMessage(),
                        'error_file' => $e->getFile(),
                        'error_line' => $e->getLine()
                    ]);

                    $output->writeln("❌ Ошибка для лотереи {$lottery->id}: {$e->getMessage()}");
                }
            }

            $totalExecutionTime = round((microtime(true) - $startTime) * 1000, 2);

            $this->logger->info('🏁 Расчет количества победителей завершен', [
                'total_lotteries' => $lotteries->count(),
                'success_count' => $successCount,
                'error_count' => $errorCount,
                'total_execution_time_ms' => $totalExecutionTime
            ]);

            $output->writeln("Обработано: {$lotteries->count()}, успешно: {$successCount}, ошибок: {$errorCount}");
        } catch (Exception $e) {
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            $this->logger->error('💥 Критическая ошибка расчета количества победителей', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'execution_time_ms' => $executionTime,
                'trace' => $e->getTraceAsString()
            ]);

            $output->writeln("Критическая ошибка: {$e->getMessage()}");
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    protected function configure(): void
    {
        $this->setName(self::$defaultName)
            ->setDescription(self::$defaultDescription)
            ->addOption('lottery-id', null, InputOption::VALUE_REQUIRED, 'ID конкретной лотереи для расчета (по умолчанию - все подходящие)');
    }
}
