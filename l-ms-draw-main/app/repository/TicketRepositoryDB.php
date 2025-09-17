<?php

namespace app\repository;

use app\classes\Interfaces\TicketRepositoryInterface;
use app\model\Ticket;
use Carbon\Carbon;
use support\Db;

class TicketRepositoryDB implements TicketRepositoryInterface
{
    public function createPartitionIfNotExists(string $tableName, int $lotteryId): void
    {
        $partitionName = "{$tableName}_lottery_{$lotteryId}";
        
        // Проверяем существование партиции
        $exists = Db::select("
            SELECT 1 FROM information_schema.tables 
            WHERE table_name = ? AND table_schema = current_schema()
        ", [$partitionName]);

        if (empty($exists)) {
            // Создаем партицию
            $sql = "CREATE TABLE {$partitionName} PARTITION OF {$tableName} FOR VALUES IN ({$lotteryId})";
            Db::statement($sql);
        }
    }

    public function saveTicketToPartition(string $tableName, array $ticketData): void
    {
        Db::table($tableName)->insertOrIgnore([
            'ticket_number' => $ticketData['ticket_number'],
            'lottery_id' => $ticketData['lottery_id'],
            'is_winner' => false,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }

    public function saveWinnerTickets(array $winnerTickets, int $lotteryId): void
    {
        // Получаем тип лотереи для определения таблицы
        $lotteryType = $this->getLotteryType($lotteryId);
        $tableName = $this->getTableNameByLotteryType($lotteryType);
        
        foreach ($winnerTickets as $ticket) {
            // Обновляем билеты в соответствующей партиционированной таблице
            Db::table($tableName)
                ->where('lottery_id', $lotteryId)
                ->where('ticket_number', $ticket['ticket_number'])
                ->update([
                    'winner_position' => $ticket['winner_position'],
                    'is_winner' => true,
                    'updated_at' => Carbon::now()
                ]);
        }
    }

    public function getWinnerTickets(int $lotteryId): array
    {
        // Получаем тип лотереи для определения таблицы
        $lotteryType = $this->getLotteryType($lotteryId);
        $tableName = $this->getTableNameByLotteryType($lotteryType);
        
        $results = Db::table($tableName)
            ->where('lottery_id', $lotteryId)
            ->where('is_winner', true)
            ->where('winner_position', '!=', 0)
            ->select('ticket_number', 'winner_position', 'is_winner')
            ->orderBy('winner_position', 'asc')
            ->get();
            
        // Преобразуем объекты в массивы
        return $results->map(function ($item) {
            return (array) $item;
        })->toArray();
    }

    public function getLotteryTickets(int $lotteryId, string $tableName = null): array
    {
        // Если таблица не указана, определяем по типу лотереи
        if ($tableName === null) {
            $lotteryType = $this->getLotteryType($lotteryId);
            $tableName = $this->getTableNameByLotteryType($lotteryType);
        }
        
        $results = Db::table($tableName)
            ->where('lottery_id', $lotteryId)
            ->select('ticket_number', 'lottery_id', 'is_winner', 'winner_position')
            ->get();
            
        // Преобразуем объекты в массивы
        return $results->map(function ($item) {
            return (array) $item;
        })->toArray();
    }

    /**
     * Получить тип лотереи по ID
     */
    private function getLotteryType(int $lotteryId): string
    {
        $result = Db::table('lottery_numbers')
            ->where('id', $lotteryId)
            ->select('lottery_type')
            ->first();
            
        if (!$result) {
            throw new \Exception("Лотерея с ID {$lotteryId} не найдена");
        }
        
        return $result->lottery_type;
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
            throw new \Exception("Неизвестный тип лотереи: $lotteryType");
        }

        return $tableMap[$lotteryType];
    }
}