<?php

namespace app\services;

use app\classes\Interfaces\TicketRepositoryInterface;
use app\dto\GetUserTicketDTO;
use app\model\LotteryNumber;
use support\Container;
use Webman\Exception\NotFoundException;

class TicketService
{
    /**
     * Репозиторий билетов.
     *
     * @var TicketRepositoryInterface
     */
    private TicketRepositoryInterface $ticketRepository;

    /**
     * Конструктор сервиса билетов.
     */
    public function __construct()
    {
        $this->ticketRepository = Container::make(TicketRepositoryInterface::class, []);
    }

    /**
     * Получает все лотереи.
     *
     * @param string|null $lotteryType
     * @param string|null $status
     * @param string|null $countryCode
     * @return array
     */
    public function getLotteries(?string $lotteryType, ?string $status, ?string $countryCode = null): array
    {
        return $this->ticketRepository->getLotteries($lotteryType, $status, $countryCode);
    }

    private function addLeaderBoardData(LotteryNumber $lottery): array
    {
        $dto = $lottery->getDTO();
        $leaderBoard = new LeaderBoardService($dto["id"]);
        $leaderBoardData = $leaderBoard->getLeaderBoard();
        $dto["total_fund"] = $leaderBoardData['prize_fund'];
        $dto["participants"] = $leaderBoardData['players_quantity'];
        return $dto;
    }

    /**
     * Получает билеты по заданным данным.
     *
     * @param array $data Массив данных для фильтрации билетов
     * @return array
     * @throws \Exception
     */
    public function getTickets(array $data): array
    {
        // мы должны получить случайные билеты или билеты с определенной маской и пагинацией
        return $this->ticketRepository->getTickets($data);
    }

    /**
     * Получает информацию о лотерее.
     *
     * @param int $lotteryId ID лотереи
     * @return array
     */
    public function getLotteryInfo(int $lotteryId): array
    {
        return $this->ticketRepository->getLotteryInfo($lotteryId);
    }

    /**
     * Получает билеты пользователя по заданным данным.
     *
     * @param int $userId ID пользователя
     * @param array $data Массив данных для фильтрации билетов
     * @return array
     */
    public function getUserTickets(int $userId, array $data): array
    {
        $status = $data['status'] ?? null;
        $lotteryId = $data['lottery_id'] ?? null;

        return $this->ticketRepository->getUserTickets($userId, $status, $lotteryId);
    }

    /**
     * Получает билет пользователя по id билета
     *
     * @param GetUserTicketDTO $dto
     * @return array
     * @throws NotFoundException
     */
    public function getUserTicket(GetUserTicketDTO $dto): array
    {
        return $this->ticketRepository->getUserTicket($dto);
    }

    /**
     * Получает активные лотереи.
     *
     * @return array
     */
    public function getActiveLotteries(): array
    {
        return $this->ticketRepository->getActiveLotteries();
    }

    /**
     * Получает ID активной лотереи по типу.
     *
     * @param string $type Тип лотереи (daily, weekly, monthly, yearly)
     * @return int|null
     */
    public function getActiveLotteryIdByType(string $type): ?int
    {
        return $this->ticketRepository->getActiveLotteryIdByType($type);
    }

    /**
     * Получает ID всех активных лотерей по типу.
     *
     * @param string $type Тип лотереи (daily_fixed, daily_dynamic, jackpot, supertour)
     * @return array Массив ID лотерей
     */
    public function getActiveLotteryIdsByType(string $type): array
    {
        return $this->ticketRepository->getActiveLotteryIdsByType($type);
    }

    /**
     * Проверяет, соответствует ли лотерея указанному типу.
     *
     * @param int $lotteryId ID лотереи
     * @param string $type Тип лотереи (daily, weekly, monthly, yearly)
     * @return bool
     */
    public function isLotteryOfType(int $lotteryId, string $type): bool
    {
        return $this->ticketRepository->isLotteryOfType($lotteryId, $type);
    }

    /**
     * Обновляет статус билетов.
     *
     * @param array $ticketIds Массив ID билетов
     * @param array $status Массив статусов для обновления
     * @return void
     */
    public function updateTicketStatus(array $ticketIds, array $status): void
    {
        $this->ticketRepository->updateTicketStatus($ticketIds, $status);
    }

    /**
     * Получает билеты для экспорта.
     *
     * @param int $lotteryId ID лотереи
     * @param int $limit Ограничение на количество билетов
     * @return array
     */
    public function getUserTicketsForExport(int $lotteryId, int $limit): array
    {
        return $this->ticketRepository->getUserTicketsForExport($lotteryId, $limit);
    }

    /**
     * Получает статистику билетов пользователя.
     *
     * @param int $userId ID пользователя
     * @return array
     */
    public function getUserStatistics(int $userId): array
    {
        return $this->ticketRepository->getUserStatistics($userId);
    }

    /**
     * Помечает лотереи как отправленные в расписании.
     *
     * @param array $lotteryIds Массив ID лотерей
     * @return void
     */
    public function markLotteriesScheduleExported(array $lotteryIds): void
    {
        $this->ticketRepository->markLotteriesScheduleExported($lotteryIds);
    }

    /**
     * Помечает лотерею как отправленную в конфигурации победителей.
     *
     * @param int $lotteryId ID лотереи
     * @return void
     */
    public function markLotteryWinnersConfigExported(int $lotteryId): void
    {
        $this->ticketRepository->markLotteryWinnersConfigExported($lotteryId);
    }

    /**
     * Получает лотереи готовые к экспорту конфигурации победителей.
     *
     * @return array Массив лотерей с рассчитанными победителями, готовых к экспорту
     */
    public function getLotteriesReadyForWinnersConfigExport(): array
    {
        return $this->ticketRepository->getLotteriesReadyForWinnersConfigExport();
    }

    /**
     * Помечает билеты как экспортированные, устанавливая дату экспорта.
     *
     * @param array $ticketIds Массив ID билетов
     * @return void
     */
    public function markTicketsExported(array $ticketIds): void
    {
        $this->ticketRepository->markTicketsExported($ticketIds);
    }
}
