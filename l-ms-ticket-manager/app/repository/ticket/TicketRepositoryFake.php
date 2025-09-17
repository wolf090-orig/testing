<?php

namespace app\repository\ticket;

use app\classes\Interfaces\TicketRepositoryInterface;
use app\dto\GetUserTicketDTO;

class TicketRepositoryFake implements TicketRepositoryInterface
{
    public function getLotteries(?string $lotteryType, ?string $status = 'active', ?string $countryCode = null): array
    {
        return [
            [
                'id' => 3158,
                'lottery_type_id' => 1,
                "lottery_name" => '194-D-24',
                "draw_date" => '2024-07-12',
            ],
            [
                'id' => 3154,
                'lottery_type_id' => 2,
                "lottery_name" => '028-W-24',
                "draw_date" => '2024-07-14',
            ],
            [
                'id' => 3146,
                'lottery_type_id' => 3,
                "lottery_name" => '007-M-24',
                "draw_date" => '2024-07-31',
            ],
            [
                'id' => 2932,
                'lottery_type_id' => 4,
                "lottery_name" => '001-Y-24',
                "draw_date" => '2024-12-31',
            ]
        ];
    }

    public function getTickets(array $data): array
    {
        return [
            "data" => [
                [
                    "id" => 0,
                    "name" => "string",
                    "price" => 0,
                    "number" => "string"
                ]
            ],
            "pagination" => [
                "total" => 0,
                "page" => 1,
                "page_size" => 10
            ]
        ];
    }

    public function getLotteryInfo(int $lotteryId): array
    {
        return [
            "players_quantity" => 0,
            "prize_fund" => 0,
            "prize_details" => [
                [
                    "position" => 1,
                    "amount" => 100
                ],
            ]
        ];
    }

    public function getUserTickets(int $userId, ?string $status, int $lotteryId = null): array
    {
        return [];
    }

    public function getActiveLotteries(): array
    {
        return [];
    }

    public function getActiveLotteryIdByType(string $type): ?int
    {
        return null;
    }

    public function isLotteryOfType(int $lotteryId, string $type): bool
    {
        return false;
    }

    public function updateTicketStatus(array $ticketIds, array $status): void {}

    public function getUserTicketsForExport(int $lotteryId, int $limit): array
    {
        return [];
    }

    public function findUserTicketsByNumbers(array $ticketNumbers, int $lotteryId): array
    {
        return [];
    }

    public function saveWinnerTicketsBatch(array $winnerTickets, int $lotteryId): void
    {
        // TODO: Implement saveWinnerTicketsBatch() method.
    }

    public function canDrawLottery(int $lotteryId): bool
    {
        return false;
    }

    public function lotteryDrawn(int $lotteryId): void
    {
        // TODO: Implement lotteryDrawn() method.
    }

    public function getUserTicket(GetUserTicketDTO $dto): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getUserStatistics(int $userId): array
    {
        return [
            'tickets_total' => 10,
            'tickets_active' => 3,
            'tickets_history' => 7,
            'tickets_winner' => 2,
            'winnings_total' => 5000
        ];
    }
}
