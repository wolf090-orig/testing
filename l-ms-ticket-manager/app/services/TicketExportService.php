<?php

namespace app\services;

use app\libraries\kafka\messages\KafkaProducerMessage;
use app\libraries\kafka\producers\Producer;
use Exception;
use Psr\Log\LoggerInterface;
use support\Db;
use support\Log;

class TicketExportService
{
    private Producer $producer;
    private TicketService $ticketService;
    private LoggerInterface $logger;

    public function __construct(string $topic, ?LoggerInterface $logger = null)
    {
        $this->producer = Producer::createFromConfigKey('tickets', config($topic));
        $this->ticketService = new TicketService(); // Используем new вместо Container::get
        $this->logger = $logger ?: Log::channel();
    }

    public function exportTickets(int $lotteryId, int $chunkSize, int $maxTickets): void
    {
        $this->logger->info('Начинаем процесс экспорта билетов');

        $this->logger->info("Получение билетов для лотереи с ID: $lotteryId");
        $tickets = $this->ticketService->getUserTicketsForExport($lotteryId, $maxTickets);
        $this->logger->info("Получено билетов: " . count($tickets));

        if (empty($tickets)) {
            $this->logger->info("Не найдено билетов для лотереи с ID: $lotteryId");
            return;
        }

        $this->logger->info('Начинаем обработку билетов по чанкам');
        $this->processTicketsInChunks($tickets, $chunkSize, $lotteryId);

        return;
    }

    private function processTicketsInChunks(array $tickets, int $chunkSize, int $lotteryId): void
    {
        $chunks = array_chunk($tickets, $chunkSize);
        foreach ($chunks as $chunkIndex => $chunk) {
            $ticketNumbers = array_column($chunk, 'ticket_number');
            $ticketIds = array_column($chunk, 'id');

            $this->logger->info("Обработка чанка $chunkIndex, количество билетов: " . count($ticketNumbers));

            if (!empty($ticketNumbers)) {
                $this->processChunk($chunkIndex, $ticketNumbers, $ticketIds, $lotteryId);
            }
        }
    }

    private function processChunk(int $chunkIndex, array $ticketNumbers, array $ticketIds, int $lotteryId): void
    {
        $messageData = [
            'lottery_id' => $lotteryId,
            'tickets' => $ticketNumbers
        ];

        $kafkaMessage = new KafkaProducerMessage($messageData);
        $kafkaMessage->withHeaders(['lottery_id' => $lotteryId]);

        $this->logger->info("Начинаем транзакцию для чанка $chunkIndex");
        Db::beginTransaction();

        try {
            $this->logger->info("Отправка сообщения в Kafka для лотереи с ID: $lotteryId");
            $this->producer->sendMessage($kafkaMessage);

            $this->logger->info("Сообщение отправлено в Kafka. Обновление даты экспорта билетов");
            $this->ticketService->markTicketsExported($ticketIds);

            Db::commit();
            $this->logger->info("Статус билетов обновлен для чанка $chunkIndex, лотереи с ID: $lotteryId");
        } catch (Exception $e) {
            Db::rollBack();
            $this->logger->error(
                "Ошибка обработки чанка $chunkIndex для лотереи с ID: $lotteryId. Ошибка: " . $e->getMessage()
            );
        }
    }

    public function getActiveLotteryIdByType(string $type): ?int
    {
        return $this->ticketService->getActiveLotteryIdByType($type);
    }

    public function getActiveLotteryIdsByType(string $type): array
    {
        return $this->ticketService->getActiveLotteryIdsByType($type);
    }
}
