<?php

namespace app\classes\Interfaces;

use app\exceptions\DestroyBasketRepositoryException;
use app\exceptions\PayBasketRepositoryException;
use Exception;

interface BasketRepositoryInterface
{
    public function getBasket(int $userId): array;

    public function addBasket(int $userId, array $ticketNumberIds, $lotteryId): array;

    /**
     * @throws PayBasketRepositoryException
     * @throws Exception
     */
    public function payBasket(int $userId): array;

    /**
     * @throws DestroyBasketRepositoryException
     * @throws Exception
     */
    public function destroyBasket(int $userId, ?int $ticketId): void;

    public function getRandomTickets(int $lotteryId, int $quantity): array;
}
