<?php

namespace app\command;

use app\services\TicketImportService;
use Closure;

/**
 * Консьюмер для билетов ежедневной лотереи с динамическим призом
 */
class ConsumeDailyDynamicTicketsCommand extends ConsumeBase
{
    protected static string $defaultName = 'consume-tickets:daily-dynamic';
    protected static string $defaultDescription = 'Консьюмер билетов daily_dynamic_tickets_v1';

    private TicketImportService $ticketImportService;

    public function __construct()
    {
        parent::__construct();
        $this->ticketImportService = new TicketImportService();
    }

    public function setUp(): void
    {
        $this->topic = config('kafka.daily_dynamic_tickets_topic');
    }

    protected function getLogChannel(): string
    {
        return 'command_consume_daily_dynamic_tickets';
    }

    public function consumerLogic(): Closure
    {
        return function (array $body, array $headers, $logger) {
            $lotteryId = $body['lottery_id'] ?? null;
            $tickets = $body['tickets'] ?? null;

            if (!$lotteryId || !$tickets || !is_array($tickets)) {
                throw new \Exception('Отсутствуют обязательные поля: lottery_id или tickets (массив)');
            }

            // Создаем партицию если не существует
            $this->ticketImportService->createPartitionIfNotExists('daily_dynamic_tickets', $lotteryId);

            // Сохраняем все билеты из массива
            $savedCount = 0;
            foreach ($tickets as $ticketNumber) {
                $ticketData = [
                    'lottery_id' => $lotteryId,
                    'ticket_number' => $ticketNumber
                ];
                
                $this->ticketImportService->saveTicketToPartition('daily_dynamic_tickets', $ticketData);
                $savedCount++;
            }

            $logger->info('Билеты daily_dynamic сохранены', [
                'lottery_id' => $lotteryId,
                'tickets_count' => $savedCount,
                'sample_tickets' => array_slice($tickets, 0, 3), // Первые 3 для примера
            ]);
        };
    }


} 