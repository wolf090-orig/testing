<?php

namespace app\services;

use app\classes\Interfaces\BasketRepositoryInterface;
use Exception;
use support\Container;

class BasketService
{
    private int $userId = 0;
    private BasketRepositoryInterface $basketRepository;

    public function __construct()
    {
        $this->basketRepository = Container::make(BasketRepositoryInterface::class, []);
    }


    public function setUserId(int $userId): void
    {
        $this->userId = $userId;
    }

    public function getBasket(): array
    {
        return $this->basketRepository->getBasket($this->userId);
    }

    /**
     * @throws Exception
     */
    public function addBasket(array $ticketNumberIds, int $lotteryId, int $quantity): array
    {
        $ticketsIds = $ticketNumberIds;

        // Если указано количество билетов и массив билетов пуст, генерируем случайные билеты
        if ($quantity > 0 && empty($ticketsIds)) {
            $tickets = $this->basketRepository->getRandomTickets($lotteryId, $quantity);

            if (empty($tickets)) {
                throw new Exception('Нет доступных билетов для выбранной лотереи.');
            }

            $ticketsIds = array_column($tickets, 'id');
        }

        // Проверяем, что в итоге есть какие-то билеты для добавления
        if (empty($ticketsIds)) {
            throw new Exception('Не указаны билеты для добавления в корзину.');
        }

        return $this->basketRepository->addBasket($this->userId, $ticketsIds, $lotteryId);
    }

    public function payBasket(): array
    {
        return $this->basketRepository->payBasket($this->userId);
    }

    public function destroyBasket(?int $ticketId = null): void
    {
        $this->basketRepository->destroyBasket($this->userId, $ticketId);
    }

}
