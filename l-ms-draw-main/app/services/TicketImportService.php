<?php

namespace app\services;

use app\repository\TicketRepositoryDB;

class TicketImportService
{
    private TicketRepositoryDB $ticketRepository;

    public function __construct()
    {
        $this->ticketRepository = new TicketRepositoryDB();
    }

    /**
     * Создать партицию для лотереи если не существует
     */
    public function createPartitionIfNotExists(string $tableName, int $lotteryId): void
    {
        $this->ticketRepository->createPartitionIfNotExists($tableName, $lotteryId);
    }

    /**
     * Сохранить билет в партиционированную таблицу
     */
    public function saveTicketToPartition(string $tableName, array $ticketData): void
    {
        $this->ticketRepository->saveTicketToPartition($tableName, $ticketData);
    }
} 