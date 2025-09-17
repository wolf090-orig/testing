<?php

namespace app\classes\Interfaces;

use app\dto\GetUserTicketDTO;
use Exception;
use Webman\Exception\NotFoundException;

interface TicketRepositoryInterface
{
    /**
     * Получает все лотереи по одному типу, которые активны на сегодняшний день.
     *
     * @param string|null $lotteryType
     * @param string|null $status
     * @param string|null $countryCode
     * @return array
     */
    public function getLotteries(?string $lotteryType, ?string $status = 'active', ?string $countryCode = null): array;

    /**
     * Получает билеты по заданным данным.
     *
     * @param array $data Массив данных для фильтрации билетов
     * @return array
     * @throws Exception
     */
    public function getTickets(array $data): array;

    /**
     * Получает информацию о лотерее.
     *
     * @param int $lotteryId ID лотереи
     * @return array
     * @throws Exception
     */
    public function getLotteryInfo(int $lotteryId): array;

    /**
     * Получает билеты пользователя по заданным данным.
     *
     * @param int $userId ID пользователя
     * @param string|null $status Статус билетов (active, history)
     * @param int|null $lotteryId ID лотереи (опционально)
     * @return array
     */
    public function getUserTickets(int $userId, ?string $status, int $lotteryId = null): array;

    /**
     * Получает билет пользователя по id билета
     *
     * @param GetUserTicketDTO $dto
     * @return array
     * @throws NotFoundException
     */
    public function getUserTicket(GetUserTicketDTO $dto): array;

    /**
     * Получает активные лотереи на текущий день.
     *
     * @return array
     */
    public function getActiveLotteries(): array;

    /**
     * Получает ID активной лотереи по типу.
     *
     * @param string $type Тип лотереи (daily, weekly, monthly, yearly)
     * @return int|null
     */
    public function getActiveLotteryIdByType(string $type): ?int;

    /**
     * Получает ID всех активных лотерей по типу.
     *
     * @param string $type Тип лотереи (daily_fixed, daily_dynamic, jackpot, supertour)
     * @return array Массив ID лотерей
     */
    public function getActiveLotteryIdsByType(string $type): array;

    /**
     * Проверяет, соответствует ли лотерея указанному типу.
     *
     * @param int $lotteryId ID лотереи
     * @param string $type Тип лотереи (daily, weekly, monthly, yearly)
     * @return bool
     */
    public function isLotteryOfType(int $lotteryId, string $type): bool;

    /**
     * Обновляет статус билетов.
     *
     * @param array $ticketIds Массив ID билетов
     * @param array $status Массив статусов для обновления
     * @return void
     */
    public function updateTicketStatus(array $ticketIds, array $status): void;

    /**
     * Получает билеты для экспорта.
     *
     * @param int $lotteryId ID лотереи
     * @param int $limit Ограничение на количество билетов
     * @return array
     */
    public function getUserTicketsForExport(int $lotteryId, int $limit): array;

    /**
     * Находит билеты пользователей по номерам билетов и ID лотереи.
     *
     * @param array $ticketNumbers Массив номеров билетов
     * @param int $lotteryId ID лотереи
     * @return array Ассоциативный массив билетов по номерам билетов
     */
    public function findUserTicketsByNumbers(array $ticketNumbers, int $lotteryId): array;

    /**
     * Сохраняет пачку выигрышных билетов.
     *
     * @param array $winnerTickets Массив данных для сохранения выигрышных билетов
     * @param int $lotteryId ID лотереи
     * @return void
     */
    public function saveWinnerTicketsBatch(array $winnerTickets, int $lotteryId): void;

    /**
     * Проверяет, можно ли проводить розыгрыш лотереи.
     *
     * @param int $lotteryId ID лотереи
     * @return bool
     */
    public function canDrawLottery(int $lotteryId): bool;

    /**
     * Обозначает, что лотерея была разыграна.
     *
     * @param int $lotteryId ID лотереи
     * @return void
     */
    public function lotteryDrawn(int $lotteryId): void;

    /**
     * Получает статистику билетов пользователя.
     *
     * @param int $userId ID пользователя
     * @return array Массив со статистикой пользователя (активные, архивные, выигрышные билеты и сумма выигрышей)
     */
    public function getUserStatistics(int $userId): array;

    /**
     * Помечает лотереи как отправленные в расписании.
     *
     * @param array $lotteryIds Массив ID лотерей
     * @return void
     */
    public function markLotteriesScheduleExported(array $lotteryIds): void;

    /**
     * Помечает лотерею как отправленную в конфигурации победителей.
     *
     * @param int $lotteryId ID лотереи
     * @return void
     */
    public function markLotteryWinnersConfigExported(int $lotteryId): void;

    /**
     * Получает лотереи готовые к экспорту конфигурации победителей.
     *
     * @return array Массив лотерей с рассчитанными победителями, готовых к экспорту
     */
    public function getLotteriesReadyForWinnersConfigExport(): array;

    /**
     * Помечает билеты как экспортированные, устанавливая дату экспорта.
     *
     * @param array $ticketIds Массив ID билетов
     * @return void
     */
    public function markTicketsExported(array $ticketIds): void;
}
