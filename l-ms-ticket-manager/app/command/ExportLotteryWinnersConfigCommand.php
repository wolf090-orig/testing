<?php

namespace app\command;

use app\libraries\kafka\messages\KafkaProducerMessage;
use app\libraries\kafka\producers\Producer;
use app\services\LeaderBoardService;
use app\services\TicketService;
use app\model\LotteryNumber;
use Carbon\Carbon;
use Exception;
use Psr\Log\LoggerInterface;
use support\Log;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ExportLotteryWinnersConfigCommand extends Command
{
    protected static string $defaultName = 'export_lottery_winners_config:run';
    protected static string $defaultDescription = 'Отправить конфигурацию победителей лотереи в Kafka для ms-draw-service';

    private Producer $producer;
    private LoggerInterface $logger;
    private TicketService $ticketService;





    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->producer = Producer::createFromConfigKey(
            'tickets',
            config('kafka.lottery_draw_config_topic', 'lottery-draw-config')
        );
        $this->logger = Log::channel('command_export_lottery_winners_config');
        $this->ticketService = new TicketService();
        
        $startTime = microtime(true);
        $specificLotteryId = (int) $input->getOption('lottery-id');

        $this->logger->info('🎯 Запуск экспорта конфигурации победителей', [
            'specific_lottery_id' => $specificLotteryId ?: 'автоматический поиск'
        ]);

        try {
            // Получаем лотереи готовые к экспорту
            if ($specificLotteryId) {
                // Принудительный экспорт конкретной лотереи
                $lotteries = $this->getLotteryById($specificLotteryId);
            } else {
                // Автоматический поиск готовых лотерей
                $lotteries = $this->ticketService->getLotteriesReadyForWinnersConfigExport();
            }

            if (empty($lotteries)) {
                $executionTime = round((microtime(true) - $startTime) * 1000, 2);
                $this->logger->info('🔍 Нет лотерей готовых к экспорту конфигурации победителей', [
                    'execution_time_ms' => $executionTime
                ]);
                $output->writeln("Нет лотерей готовых к экспорту конфигурации победителей");
                return Command::SUCCESS;
            }

            $this->logger->info('📋 Найдены лотереи для экспорта конфигурации', [
                'count' => count($lotteries),
                'lottery_ids' => array_column($lotteries, 'id')
            ]);

            $successCount = 0;
            $errorCount = 0;

            foreach ($lotteries as $lotteryData) {
                try {
                    $lotteryStartTime = microtime(true);
                    $lotteryId = $lotteryData['id'];
                    
                    // Получаем дополнительную статистику для логирования
                    $leaderBoardService = new LeaderBoardService($lotteryId);
                    $leaderBoard = $leaderBoardService->getLeaderBoard();
                    
                    // Формируем сообщение для Kafka
                    $body = [
                        'event_type' => 'lottery_winners_config',
                        'lottery_id' => $lotteryId,
                        'lottery_type' => $lotteryData['lottery_type'],
                        'calculated_winners_count' => $lotteryData['calculated_winners_count'],
                        'total_participants' => $leaderBoard['players_quantity'],
                        'total_tickets_sold' => $leaderBoard['paid_tickets_count'],
                        'timestamp' => Carbon::now()->toISOString()
                    ];

                    // Отправляем в Kafka
                    $message = new KafkaProducerMessage($body);
                    $this->producer->sendMessage($message);

                    // Помечаем как отправленную
                    $this->ticketService->markLotteryWinnersConfigExported($lotteryId);

                    $lotteryExecutionTime = round((microtime(true) - $lotteryStartTime) * 1000, 2);
                    $successCount++;

                    $this->logger->info('✅ Конфигурация победителей отправлена в Kafka', [
                        'lottery_id' => $lotteryId,
                        'lottery_name' => $lotteryData['lottery_name'],
                        'lottery_type' => $lotteryData['lottery_type'],
                        'winners_count' => $lotteryData['calculated_winners_count'],
                        'participants' => $leaderBoard['players_quantity'],
                        'tickets_sold' => $leaderBoard['paid_tickets_count'],
                        'execution_time_ms' => $lotteryExecutionTime
                    ]);

                    $output->writeln("✅ Лотерея {$lotteryId} ({$lotteryData['lottery_name']}): {$lotteryData['calculated_winners_count']} победителей");

                } catch (Exception $e) {
                    $errorCount++;
                    $lotteryExecutionTime = round((microtime(true) - $lotteryStartTime) * 1000, 2);

                    $this->logger->error('❌ Ошибка при экспорте конфигурации лотереи', [
                        'lottery_id' => $lotteryData['id'],
                        'lottery_name' => $lotteryData['lottery_name'],
                        'error' => $e->getMessage(),
                        'execution_time_ms' => $lotteryExecutionTime
                    ]);

                    $output->writeln("❌ Ошибка лотереи {$lotteryData['id']}: {$e->getMessage()}");
                }
            }

            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            $this->logger->info('🏁 Экспорт конфигурации победителей завершен', [
                'processed_lotteries' => count($lotteries),
                'success_count' => $successCount,
                'error_count' => $errorCount,
                'execution_time_ms' => $executionTime
            ]);

            $output->writeln("Завершено: обработано {$successCount} лотерей, ошибок: {$errorCount}, время: {$executionTime}ms");

            return $errorCount > 0 ? Command::FAILURE : Command::SUCCESS;

        } catch (Exception $e) {
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            $this->logger->error('💥 Критическая ошибка при экспорте конфигурации', [
                'error' => $e->getMessage(),
                'execution_time_ms' => $executionTime
            ]);

            $output->writeln("Критическая ошибка: {$e->getMessage()}, время: {$executionTime}ms");
            return Command::FAILURE;
        }
    }

    /**
     * Получает данные конкретной лотереи по ID для принудительного экспорта
     */
    private function getLotteryById(int $lotteryId): array
    {
        // Получаем лотерею напрямую для принудительного экспорта
        $lotteryInfo = $this->ticketService->getLotteryInfo($lotteryId);
        
        if (empty($lotteryInfo)) {
            $this->logger->error('Лотерея не найдена', ['lottery_id' => $lotteryId]);
            return [];
        }

        // Проверяем базовые условия
        if ($lotteryInfo['status'] === 'history') {
            $this->logger->error('Лотерея уже разыграна', ['lottery_id' => $lotteryId]);
            throw new Exception("Лотерея {$lotteryId} уже разыграна");
        }

        // Получаем calculated_winners_count из модели напрямую если не хватает данных
        $lottery = LotteryNumber::find($lotteryId);
        if (!$lottery || is_null($lottery->calculated_winners_count)) {
            throw new Exception("Для лотереи {$lotteryId} не рассчитано количество победителей");
        }

        // Возвращаем в формате как из репозитория
        return [[
            'id' => $lotteryInfo['id'],
            'lottery_name' => $lotteryInfo['name'],
            'lottery_type' => $lotteryInfo['type_name'],
            'end_date' => $lotteryInfo['sale_end_date'],
            'draw_date' => $lotteryInfo['draw_date'],
            'calculated_winners_count' => $lottery->calculated_winners_count,
        ]];
    }

    protected function configure(): void
    {
        $this->setName(self::$defaultName)
            ->setDescription(self::$defaultDescription)
            ->addOption('lottery-id', null, InputOption::VALUE_OPTIONAL, 'ID конкретной лотереи для принудительного экспорта (опционально, без параметра - автоматический поиск готовых лотерей)');
    }
}
