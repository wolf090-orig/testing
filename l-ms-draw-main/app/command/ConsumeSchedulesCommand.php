<?php

namespace app\command;

use app\services\LotteryService;
use Closure;

/**
 * Консьюмер для расписаний лотерей
 */
class ConsumeSchedulesCommand extends ConsumeBase
{
    protected static string $defaultName = 'consume-schedules:run';
    protected static string $defaultDescription = 'Консьюмер расписаний lottery_schedules_v1';

    private LotteryService $lotteryService;

    public function __construct()
    {
        parent::__construct();
        $this->lotteryService = new LotteryService();
    }

    public function setUp(): void
    {
        $this->topic = config('kafka.lottery_schedules_topic');
    }

    protected function getLogChannel(): string
    {
        return 'command_consume_schedules';
    }

    public function consumerLogic(): Closure
    {
        return function (array $body, array $headers, $logger) {
            // Проверяем, что пришел массив лотерей
            if (!isset($body['lotteries']) || !is_array($body['lotteries'])) {
                throw new \Exception('Отсутствует массив lotteries в сообщении');
            }

            $lotteries = $body['lotteries'];
            $processedCount = 0;

            foreach ($lotteries as $lottery) {
                $lotteryId = $lottery['id'] ?? null;
                $lotteryName = $lottery['lottery_name'] ?? null;

                if (!$lotteryId || !$lotteryName) {
                    $logger->warning('Пропущена лотерея с отсутствующими обязательными полями', [
                        'lottery_data' => $lottery
                    ]);
                    continue;
                }

                // Сохраняем расписание лотереи
                $this->saveLotterySchedule($lottery);
                $processedCount++;

                $logger->info('Расписание лотереи сохранено', [
                    'lottery_id' => $lotteryId,
                    'lottery_name' => $lotteryName,
                ]);
            }

            $logger->info('Обработка расписаний завершена', [
                'total_received' => count($lotteries),
                'processed_count' => $processedCount,
                'skipped_count' => count($lotteries) - $processedCount,
            ]);
        };
    }

    /**
     * Сохранить расписание лотереи
     */
    private function saveLotterySchedule(array $scheduleData): void
    {
        $this->lotteryService->saveLotterySchedule($scheduleData);
    }
} 