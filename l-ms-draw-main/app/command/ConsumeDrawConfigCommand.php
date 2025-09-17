<?php

namespace app\command;

use app\services\LotteryService;
use Closure;
use Exception;

/**
 * Консьюмер для конфигурации розыгрышей
 */
class ConsumeDrawConfigCommand extends ConsumeBase
{
    protected static string $defaultName = 'consume-draw-config:run';
    protected static string $defaultDescription = 'Консьюмер конфигурации lottery_draw_config_v1';

    private LotteryService $lotteryService;

    public function __construct()
    {
        parent::__construct();
        $this->lotteryService = new LotteryService();
    }

    public function setUp(): void
    {
        $this->topic = config('kafka.lottery_draw_config_topic');
    }

    protected function getLogChannel(): string
    {
        return 'command_consume_draw_config';
    }

    public function consumerLogic(): Closure
    {
        return function (array $body, array $headers, $logger) {
            $lotteryId = $body['lottery_id'] ?? null;
            $winnersCount = $body['calculated_winners_count'] ?? null;

            if (!$lotteryId || $winnersCount === null) {
                throw new Exception('Отсутствуют обязательные поля: lottery_id или calculated_winners_count');
            }

            // Обновляем конфигурацию розыгрыша
            $this->updateDrawConfig($lotteryId, $body);

            $logger->info('Конфигурация розыгрыша обновлена', [
                'lottery_id' => $lotteryId,
                'winners_count' => $winnersCount,
            ]);
        };
    }

    /**
     * Обновить конфигурацию розыгрыша
     */
    private function updateDrawConfig(int $lotteryId, array $configData): void
    {
        $this->lotteryService->updateDrawConfig($lotteryId, $configData);
    }
}
