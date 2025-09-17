<?php

namespace app\services;

use app\classes\Interfaces\LotteryRepositoryInterface;
use app\libraries\kafka\messages\KafkaProducerMessage;
use app\libraries\kafka\producers\Producer;
use Psr\Log\LoggerInterface;
use support\Container;
use support\Log;

class ExportService
{
    public LoggerInterface $log;
    private Producer $producer;
    private LotteryRepositoryInterface $lotteryRepository;

    public function __construct()
    {
        $this->log = Log::channel('default');
        $this->lotteryRepository = Container::make(LotteryRepositoryInterface::class, []);
    }

    /**
     * Получить лотереи готовые к розыгрышу
     * Условия:
     * 1. Время розыгрыша наступило (draw_date < now)
     * 2. Розыгрыш еще не проведен (drawn_at IS NULL)
     * 3. Лотерея активна (is_active = true)
     * 4. Есть конфигурация победителей (calculated_winners_count IS NOT NULL)
     */
    public function getLotteries2Draw()
    {
        $this->log->info("Ищем лотереи для розыгрыша");
        
        $lotteries = $this->lotteryRepository->getLotteries2Draw();
            
        $this->log->info("Найдено лотерей для розыгрыша: " . count($lotteries), [
            'lottery_ids' => array_column($lotteries, 'id')
        ]);
        
        return $lotteries;
    }

    public function publish(array $publicMessage)
    {
        $this->producer = Producer::createFromConfigKey(
            'tickets',
            config('kafka.lottery_draw_results_topic')
        );
        
        try {
            $message = new KafkaProducerMessage($publicMessage);
            $this->producer->sendMessage($message);
            
            $this->log->info('Результаты розыгрыша отправлены в Kafka', [
                'lottery_id' => $publicMessage['lottery_id'] ?? 'unknown',
                'winners_count' => count($publicMessage['tickets'] ?? [])
            ]);
        } catch (\Exception $e) {
            $lotteryId = $publicMessage['lottery_id'] ?? 'unknown';
            $this->log->error('Не удалось отправить результаты розыгрыша', [
                'lottery_id' => $lotteryId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
