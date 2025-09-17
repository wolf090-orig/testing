<?php

namespace app\services;

use app\classes\Interfaces\LotteryRepositoryInterface;
use app\classes\Interfaces\MsTicketManagerInterface;
use app\classes\Interfaces\RandomizerClientInterface;
use app\classes\Interfaces\TicketRepositoryInterface;
use app\model\Ticket;
use Exception;
use Psr\Log\LoggerInterface;
use support\Container;
use support\Log;

class DrawService
{
    public TicketRepositoryInterface $ticketRepo;
    public LotteryRepositoryInterface $lotteryRepository;
    public LoggerInterface $log;

    public function __construct()
    {
        $this->log = Log::channel('default');
        $this->ticketRepo = Container::make(TicketRepositoryInterface::class, []);
        $this->lotteryRepository = Container::make(LotteryRepositoryInterface::class, []);
    }

    public function drawLottery(int $lotteryId): bool
    {
        $this->log->info("Начинаем розыгрыш лотереи с id: $lotteryId");
        
        // Получаем лотерею через репозиторий
        $lotteryData = $this->lotteryRepository->findById($lotteryId);
        if (!$lotteryData) {
            $this->log->error("Лотерея не найдена: $lotteryId");
            return false;
        }

        $this->log->info("Лотерея найдена", [
            'lottery_id' => $lotteryId,
            'lottery_name' => $lotteryData['lottery_name'],
            'lottery_type' => $lotteryData['lottery_type'],
            'calculated_winners_count' => $lotteryData['calculated_winners_count']
        ]);

        // Проверяем наличие конфигурации победителей
        if (is_null($lotteryData['calculated_winners_count'])) {
            $this->log->error("Отсутствует конфигурация победителей для лотереи: $lotteryId");
            return false;
        }

        $this->log->info("Проверяем наличие выигрышных билетов до розыгрыша");
        $winnerTickets = $this->ticketRepo->getWinnerTickets($lotteryId);

        if (count($winnerTickets) == 0) {
            $this->log->info("Билеты чистые - начинаем розыгрыш", [
                'expected_winners' => $lotteryData['calculated_winners_count']
            ]);
            $this->draw($lotteryId, $lotteryData['calculated_winners_count'], $lotteryData['lottery_type']);
        } else {
            $this->log->warning("Найдены уже выигранные билеты до розыгрыша", [
                'existing_winners_count' => count($winnerTickets)
            ]);
        }

        $this->log->info("Лотерея разыгралась, отмечаем в базе");
        $this->lotteryRepository->markAsDrawn($lotteryId);

        $this->log->info("Розыгрыш лотереи завершен успешно", [
            'lottery_id' => $lotteryId
        ]);
        
        return true;
    }

    private function draw(int $lotteryId, int $winnersCount, string $lotteryType): void
    {
        // Получаем билеты из партиционированных таблиц по типу лотереи
        $tickets = $this->getTicketsByLotteryType($lotteryType, $lotteryId);
        
        $ticketsCount = count($tickets);
        $this->log->info("Количество билетов в розыгрыше: $ticketsCount");

        if (empty($tickets)) {
            $this->log->warning("Билетов не найдено для розыгрыша");
            return;
        }

        if ($winnersCount > $ticketsCount) {
            $this->log->warning("Количество победителей больше количества билетов", [
                'winners_count' => $winnersCount,
                'tickets_count' => $ticketsCount
            ]);
            $winnersCount = $ticketsCount;
        }

        if ($winnersCount > 0) {
            $client = Container::make(RandomizerClientInterface::class, []);
            $ticketNumbers = array_column($tickets, 'ticket_number');
            
            $this->log->info("Запускаем рандомайзер", [
                'tickets_count' => count($ticketNumbers),
                'winners_count' => $winnersCount
            ]);
            
            $winnerTickets = $client->drawFixed($ticketNumbers, $winnersCount);
            $this->ticketRepo->saveWinnerTickets($winnerTickets, $lotteryId);
            
            $this->log->info("Победители определены и сохранены", [
                'winners_count' => count($winnerTickets)
            ]);
        }
    }

    /**
     * Получить билеты по типу лотереи из соответствующей партиционированной таблицы
     */
    private function getTicketsByLotteryType(string $lotteryType, int $lotteryId): array
    {
        $tableName = $this->getTableNameByLotteryType($lotteryType);
        return $this->ticketRepo->getLotteryTickets($lotteryId, $tableName);
    }

    /**
     * Получить имя таблицы по типу лотереи
     */
    private function getTableNameByLotteryType(string $lotteryType): string
    {
        $tableMap = [
            'daily_fixed' => 'daily_fixed_tickets',
            'daily_dynamic' => 'daily_dynamic_tickets',
            'jackpot' => 'jackpot_tickets',
            'supertour' => 'supertour_tickets'
        ];

        if (!isset($tableMap[$lotteryType])) {
            throw new Exception("Неизвестный тип лотереи: $lotteryType");
        }

        return $tableMap[$lotteryType];
    }
}

