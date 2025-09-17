<?php

namespace app\classes\Interfaces;

interface TicketRepositoryInterface
{
    /**
     * Создать партицию для лотереи если не существует
     */
    public function createPartitionIfNotExists(string $tableName, int $lotteryId): void;

    /**
     * Сохранить билет в партиционированную таблицу
     */
    public function saveTicketToPartition(string $tableName, array $ticketData): void;

    public function saveWinnerTickets(array $winnerTickets, int $lotteryId): void;

    public function getWinnerTickets(int $lotteryId): array;

    /**
     * Получить все билеты лотереи из конкретной таблицы
     */
    public function getLotteryTickets(int $lotteryId, string $tableName = null): array;
}
