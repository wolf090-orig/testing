<?php

namespace app\command;

use app\services\TicketImportService;
use Closure;

/**
 * Консьюмер для билетов супертур лотереи
 */
class ConsumeSupertourTicketsCommand extends ConsumeBase
{
    protected static string $defaultName = 'consume-tickets:supertour';
    protected static string $defaultDescription = 'Консьюмер билетов supertour_tickets_v1';

    private TicketImportService $ticketImportService;

    public function __construct()
    {
        parent::__construct();
        $this->ticketImportService = new TicketImportService();
    }

    public function setUp(): void
    {
        $this->topic = config('kafka.supertour_tickets_topic');
    }

    protected function getLogChannel(): string
    {
        return 'command_consume_supertour_tickets';
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
            $this->ticketImportService->createPartitionIfNotExists('supertour_tickets', $lotteryId);

            // Сохраняем все билеты из массива
            $savedCount = 0;
            foreach ($tickets as $ticketNumber) {
                $ticketData = [
                    'lottery_id' => $lotteryId,
                    'ticket_number' => $ticketNumber
                ];
                
                $this->ticketImportService->saveTicketToPartition('supertour_tickets', $ticketData);
                $savedCount++;
            }

            $logger->info('Билеты supertour сохранены', [
                'lottery_id' => $lotteryId,
                'tickets_count' => $savedCount,
                'sample_tickets' => array_slice($tickets, 0, 3), // Первые 3 для примера
            ]);
        };
    }


} 