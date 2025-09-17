<?php

namespace app\repository;

use app\classes\Interfaces\TicketRepositoryInterface;
use app\model\Ticket;

class TicketRepositoryFake implements TicketRepositoryInterface
{
    public function saveWinnerTickets(array $winnerTickets, int $lotteryId): void
    {
    }

    public function getWinnerTickets(int $lotteryId): array
    {
        return [];
    }
}