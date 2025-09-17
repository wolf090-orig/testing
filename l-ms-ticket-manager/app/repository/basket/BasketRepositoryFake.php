<?php

namespace app\repository\basket;

use app\classes\Interfaces\BasketRepositoryInterface;

class BasketRepositoryFake implements BasketRepositoryInterface
{
    public function getBasket(int $userId): array
    {
        return [];
    }

    public function addBasket(int $userId, array $ticketNumberIds, $lotteryId): array
    {
        return [];
    }

    public function payBasket(int $userId): array
    {
        return [];
    }

    public function destroyBasket(int $userId, ?int $ticketId): void
    {
    }

    public function getRandomTickets(int $lotteryId, int $quantity): array
    {
        return [];
    }
}
