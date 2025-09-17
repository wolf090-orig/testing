<?php

namespace app\repository\ticket;

use app\classes\Interfaces\TicketRepositoryInterface;
use app\dto\GetUserTicketDTO;
use app\exception\NotFoundException;
use app\model\Country;
use app\model\LotteryNumber;
use app\model\LotteryTypes;
use app\model\UserTicketPurchase;
use app\model\WinnerTicket;
use app\services\LeaderBoardService;
use Carbon\Carbon;
use Exception;
use support\Db;
use support\Log;

class TicketRepositoryDB implements TicketRepositoryInterface
{
    /**
     * Константа для страны по умолчанию
     */
    private const DEFAULT_COUNTRY_CODE = 'RU';

    /**
     * Преобразует код страны в ID, с фоллбэком на Россию
     *
     * @param string|null $countryCode
     * @return int
     */
    private function getCountryIdByCode(?string $countryCode): int
    {
        if (empty($countryCode)) {
            $countryCode = self::DEFAULT_COUNTRY_CODE;
        }

        // Нормализуем код страны
        $countryCode = strtoupper(trim($countryCode));

        // Ищем страну по коду
        $country = Country::findByCode($countryCode);

        if (!$country) {
            // Если страна не найдена, используем Россию как фоллбэк
            Log::warning("Country with code '{$countryCode}' not found, using fallback to Russia");
            $country = Country::findByCode(self::DEFAULT_COUNTRY_CODE);

            if (!$country) {
                // Если даже Россия не найдена, создаем запись об ошибке
                Log::error("Default country '" . self::DEFAULT_COUNTRY_CODE . "' not found in database");
                throw new Exception("Default country configuration is missing");
            }
        }

        return $country->id;
    }

    public function getLotteries(?string $lotteryType, ?string $status = 'active', ?string $countryCode = null): array
    {
        // Преобразуем код страны в ID
        $countryId = null;
        if ($countryCode) {
            $countryId = $this->getCountryIdByCode($countryCode);
        }

        return LotteryNumber::query()
            ->selectRaw('id, lottery_type_id, lottery_name, draw_date, country_id, start_date, end_date, is_drawn, is_active')
            ->with(['price', 'type', 'country'])
            ->when($status === 'active', function ($query) {
                $query->where('draw_date', '>=', Carbon::now())
                    ->where(function ($subQuery) {
                        // Для лотерей с заполненными датами - проверяем period продаж
                        $subQuery->where(function ($dateQuery) {
                            $dateQuery->whereNotNull('start_date')
                                ->whereNotNull('end_date')
                                ->where('start_date', '<=', Carbon::now())
                                ->where('end_date', '>=', Carbon::now());
                        })
                            // Для лотерей с NULL датами (jackpot, supertour) - берем все
                            ->orWhere(function ($nullQuery) {
                                $nullQuery->whereNull('start_date')
                                    ->whereNull('end_date');
                            });
                    });
            }, function ($query) {
                $query->where('draw_date', '<', Carbon::now());
            })
            ->when(!is_null($lotteryType), function ($query) use ($lotteryType) {
                $query->whereHas('type', function ($query) use ($lotteryType) {
                    $query->where('name', $lotteryType);
                });
            })
            ->when(!is_null($countryId), function ($query) use ($countryId) {
                $query->where('country_id', $countryId);
            })
            ->orderByRaw("lottery_type_id, id asc")
            ->get()
            ->map(fn(LotteryNumber $lottery) => $this->addLeaderBoardData($lottery))
            ->toArray();
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

    /** @inheritdoc */
    public function getTickets(array $data): array
    {
        $quantity = $data['quantity'] ?? 0;
        $page = intval($data['page']) ? $data['page'] : 1;
        $pageSize = $data['page_size'] ?? 10;
        $lotteryId = $data['lottery_id'];
        $type = $data['type'] ?? 'auto';
        $mask = $data['mask'] ?? "_______";

        $lottery = LotteryNumber::where('id', $lotteryId)->first();
        if (empty($lottery)) {
            throw new Exception("Лотерея не найдена");
        }

        list(, $lotteryTable) = $lottery->getTablePartitionName();
        $tickets = LotteryNumber::getLotteryPartitionModel($lotteryTable)
            ->where('lottery_id', $lotteryId)
            ->where('is_reserved', false)
            ->where("ticket_number", 'LIKE', $mask)
            ->limit($pageSize)
            ->offset(($page - 1) * $pageSize)
            ->orderByRaw('RANDOM()')
            ->get()
            ->toArray();

        $total = LotteryNumber::getLotteryPartitionModel($lotteryTable)->where('lottery_id', $lotteryId)
            ->where("ticket_number", 'LIKE', $mask)
            ->count();

        return [
            "tickets" => $tickets,
            "pagination" => [
                "total" => $total,
                "page" => $page,
                "page_size" => $pageSize
            ]
        ];
    }

    /** @inheritdoc */
    public function getLotteryInfo(int $lotteryId): array
    {
        $lottery = LotteryNumber::where('id', $lotteryId)->first();
        if (empty($lottery)) {
            throw new Exception("Лотерея не найдена");
        }

        // Получаем базовую информацию о лотерее
        $service = new LeaderBoardService($lottery->id);
        $leaderBoardData = $service->getLeaderBoard();

        // Добавляем основную информацию о лотерее
        $lotteryData = [
            'id' => $lottery->id,
            'name' => $lottery->lottery_name,
            'type_id' => $lottery->lottery_type_id,
            'type_name' => $lottery->type->name,
            'status' => $lottery->draw_date < Carbon::now() ? 'history' : 'active',
            'price' => $lottery->getPrice(),
            'prize_fund' => $leaderBoardData['prize_fund'],
            'players_quantity' => $leaderBoardData['players_quantity'],
            'draw_date' => $lottery->draw_date,
            'sale_start_date' => $lottery->start_date,
            'sale_end_date' => $lottery->end_date,
            'prize_details' => $leaderBoardData['prize_details'],
        ];

        return $lotteryData;
    }

    /** @inheritdoc */
    public function getUserTickets(int $userId, ?string $status, int $lotteryId = null): array
    {
        $tickets = UserTicketPurchase::where('user_ticket_purchases.user_id', $userId)
            ->when($lotteryId, function ($query) use ($lotteryId) {
                $query->where('user_ticket_purchases.lottery_id', $lotteryId);
            })
            ->leftJoin('lottery_numbers as lm', 'lm.id', '=', 'user_ticket_purchases.lottery_id')
            ->join('lottery_types as lt', 'lm.lottery_type_id', '=', 'lt.id')
            // Обрабатываем статусы active, history и winner
            ->when($status === 'active', function ($query) {
                // Выбираем только активные лотереи (с будущей датой розыгрыша)
                $query->where('lm.draw_date', '>=', Carbon::now());
            })
            ->when($status === 'history', function ($query) {
                // Выбираем только прошедшие лотереи (с прошлой датой розыгрыша)
                $query->where('lm.draw_date', '<', Carbon::now());
            })
            ->when($status === 'winner', function ($query) use ($userId) {
                // Выбираем только выигрышные билеты
                $query->join('winner_tickets as wt', 'wt.user_ticket_purchase_id', '=', 'user_ticket_purchases.id')
                    ->leftJoin('payment_currencies as pc', 'pc.id', '=', 'wt.payout_currency_id')
                    ->where('wt.user_id', $userId);
            })
            ->selectRaw('
                user_ticket_purchases.id as id,
                user_ticket_purchases.lottery_id as lottery_id,
                user_ticket_purchases.ticket_id as ticket_id,
                user_ticket_purchases.purchased_at as purchased_at,
                lm.lottery_name as lottery_name,
                lm.draw_date as draw_date,
                lm.is_drawn as is_drawn,
                lt.name as lottery_type_name,
                lt.id as lottery_type_id
            ')
            ->when($status === 'winner', function ($query) {
                $query->addSelect(
                    'wt.payout_amount as win_amount',
                    'wt.winner_position as winner_position',
                    'pc.code as winning_currency_code',
                    'pc.name as winning_currency_name'
                );
            })
            ->orderBy('user_ticket_purchases.lottery_id')
            ->orderBy('user_ticket_purchases.purchased_at', 'desc')
            ->get()
            ->toArray();

        // Дополняем каждый билет номером из партиционированной таблицы
        $ticketsWithNumbers = [];
        foreach ($tickets as $ticket) {
            $lottery = LotteryNumber::find($ticket['lottery_id']);
            if ($lottery) {
                // Получаем имя партиционированной таблицы
                list(, $lotteryTable) = $lottery->getTablePartitionName();

                // Запрашиваем номер билета из партиционированной таблицы
                $ticketDetail = LotteryNumber::getLotteryPartitionModel($lotteryTable)
                    ->where('id', $ticket['ticket_id'])
                    ->where('lottery_id', $ticket['lottery_id'])
                    ->first(['ticket_number']);

                $ticket['ticket_number'] = $ticketDetail ? $ticketDetail->ticket_number : null;
            } else {
                $ticket['ticket_number'] = null;
            }

            $ticketsWithNumbers[] = $ticket;
        }

        return $ticketsWithNumbers;
    }

    /**
     * @param GetUserTicketDTO $dto
     * @return array
     * @throws NotFoundException
     */
    public function getUserTicket(GetUserTicketDTO $dto): array
    {
        $ticket = UserTicketPurchase::where('user_ticket_purchases.user_id', $dto->userId)
            ->where('user_ticket_purchases.id', $dto->ticketId)
            ->leftJoin('lottery_numbers as lm', 'lm.id', '=', 'user_ticket_purchases.lottery_id')
            ->join('lottery_types as lt', 'lm.lottery_type_id', '=', 'lt.id')
            ->leftJoin('winner_tickets as wt', function ($join) use ($dto) {
                $join->on('wt.user_ticket_purchase_id', '=', 'user_ticket_purchases.id')
                    ->where('wt.user_id', $dto->userId);
            })
            ->leftJoin('payment_currencies as pc', 'pc.id', '=', 'wt.payout_currency_id')
            ->selectRaw('
                user_ticket_purchases.id as id,
                user_ticket_purchases.lottery_id as lottery_id,
                user_ticket_purchases.ticket_id as ticket_id,
                user_ticket_purchases.purchased_at as purchased_at,
                lm.lottery_name as lottery_name,
                lm.draw_date as draw_date,
                lm.is_drawn as is_drawn,
                lt.name as lottery_type_name,
                lt.id as lottery_type_id,
                wt.payout_amount as win_amount,
                wt.winner_position as winner_position,
                pc.code as winning_currency_code,
                pc.name as winning_currency_name
            ')
            ->first();

        if (!$ticket) {
            throw new NotFoundException("Билет не найден");
        }

        $ticketArray = $ticket->toArray();

        // Получаем номер билета из партиционированной таблицы
        $lottery = LotteryNumber::find($ticketArray['lottery_id']);
        if ($lottery) {
            list(, $lotteryTable) = $lottery->getTablePartitionName();

            $ticketDetail = LotteryNumber::getLotteryPartitionModel($lotteryTable)
                ->where('id', $ticketArray['ticket_id'])
                ->where('lottery_id', $ticketArray['lottery_id'])
                ->first(['ticket_number']);

            $ticketArray['ticket_number'] = $ticketDetail ? $ticketDetail->ticket_number : null;
        } else {
            $ticketArray['ticket_number'] = null;
        }

        return $ticketArray;
    }

    /** @inheritdoc */
    public function getActiveLotteries(): array
    {
        $lotteries = LotteryNumber::query()
            ->selectRaw("id, lottery_type_id, lottery_name, draw_date, start_date, end_date")
            ->whereRaw("date(now()) <= draw_date")
            ->whereNull('schedule_exported_at')
            ->where('is_active', true)
            ->with(['price', 'lotteryType:id,name'])
            ->orderBy('id', 'asc')
            ->get()
            ->map(function (LotteryNumber $lottery) {
                return [
                    "id" => $lottery->id,
                    "sale_start_date" => $lottery->start_date,
                    "sale_end_date" => $lottery->end_date,
                    "draw_date" => $lottery->draw_date,
                    "lottery_name" => $lottery->lottery_name,
                    "lottery_type" => $lottery->lotteryType->name,
                ];
            });
        return $lotteries->toArray();
    }

    /** @inheritdoc */
    public function getActiveLotteryIdByType(string $type): ?int
    {
        $now = Carbon::now();

        return LotteryNumber::whereHas('type', function ($query) use ($type) {
            $query->where('name', $type);
        })
            ->where('is_active', true)
            ->where('is_drawn', false)
            ->where('start_date', '<=', $now)
            ->where('end_date', '>=', $now)
            ->orderBy('id', 'asc')
            ->value('id');
    }

    /** @inheritdoc */
    public function getActiveLotteryIdsByType(string $type): array
    {
        return LotteryNumber::whereHas('type', function ($query) use ($type) {
            $query->where('name', $type);
        })
            ->where('is_active', true)
            ->where('is_drawn', false)
            ->whereHas('ticketPurchases', function ($query) {
                $query->whereNull('tickets_exported_at'); // Есть неэкспортированные билеты
            })
            ->orderBy('id', 'asc')
            ->pluck('id')
            ->toArray();
    }

    /** @inheritdoc */
    public function isLotteryOfType(int $lotteryId, string $type): bool
    {
        return LotteryNumber::where('id', $lotteryId)
            ->whereHas('type', function ($query) use ($type) {
                $query->where('name', $type);
            })
            ->exists();
    }

    /** @inheritdoc */
    public function updateTicketStatus(array $ticketIds, array $status): void
    {
        UserTicketPurchase::whereIn('id', $ticketIds)->update($status);
    }

    /** @inheritdoc */
    public function getUserTicketsForExport(int $lotteryId, int $limit): array
    {
        // Получаем информацию о лотерее для определения партиции
        $lottery = LotteryNumber::where('id', $lotteryId)->first();
        if (!$lottery) {
            return [];
        }

        list(, $lotteryTable) = $lottery->getTablePartitionName();

        // Соединяем user_ticket_purchases с партиционированной таблицей билетов
        // Фильтруем только неэкспортированные билеты (tickets_exported_at IS NULL)
        return UserTicketPurchase::where('user_ticket_purchases.lottery_id', $lotteryId)
            ->whereNull('user_ticket_purchases.tickets_exported_at')
            ->join($lotteryTable, $lotteryTable . '.id', '=', 'user_ticket_purchases.ticket_id')
            ->select([
                'user_ticket_purchases.id',
                'user_ticket_purchases.ticket_id',
                'user_ticket_purchases.lottery_id',
                $lotteryTable . '.ticket_number'
            ])
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * {@inheritdoc}
     */
    public function findUserTicketsByNumbers(array $ticketNumbers, int $lotteryId): array
    {
        // Получаем информацию о лотерее для определения таблицы билетов
        $lottery = LotteryNumber::where('id', $lotteryId)->first();
        if (!$lottery) {
            return [];
        }

        // Получаем название таблицы партиции для билетов
        list(, $lotteryTable) = $lottery->getTablePartitionName();

        // Находим билеты по номерам в соответствующей таблице билетов
        $tickets = LotteryNumber::getLotteryPartitionModel($lotteryTable)
            ->whereIn('ticket_number', $ticketNumbers)
            ->where('lottery_id', $lotteryId)
            ->get();

        // Извлекаем ID билетов
        $ticketIds = $tickets->pluck('id')->toArray();

        if (empty($ticketIds)) {
            return [];
        }

        // Находим покупки билетов по ticket_id
        $userTickets = UserTicketPurchase::whereIn('ticket_id', $ticketIds)
            ->where('lottery_id', $lotteryId)
            ->get();

        // Создаем маппинг ticket_number -> user_ticket_purchase
        $result = [];
        $ticketsMap = $tickets->keyBy('id');

        foreach ($userTickets as $userTicket) {
            $ticket = $ticketsMap->get($userTicket->ticket_id);
            if ($ticket) {
                $result[$ticket->ticket_number] = $userTicket;
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function saveWinnerTicketsBatch(array $winnerTickets, int $lotteryId): void
    {
        $log = Log::channel('default');
        // Собираем идентификаторы покупок билетов
        $userTicketPurchaseIds = array_column($winnerTickets, 'user_ticket_purchase_id');
        // Получаем существующие записи чтобы избежать дубликатов
        $existingTickets = WinnerTicket::whereIn('user_ticket_purchase_id', $userTicketPurchaseIds)
            ->get()
            ->pluck('user_ticket_purchase_id')
            ->toArray();
        $log->info("Взяли из БД существующие выигрышные билеты", ['existing' => $existingTickets]);

        // Подготавливаем данные для вставки новых записей
        $insertData = [];
        foreach ($winnerTickets as $winnerTicket) {
            if (!in_array($winnerTicket['user_ticket_purchase_id'], $existingTickets)) {
                $insertData[] = [
                    'user_ticket_purchase_id' => $winnerTicket['user_ticket_purchase_id'],
                    'lottery_id' => $lotteryId,
                    'user_id' => $winnerTicket['user_id'],
                    'winner_position' => $winnerTicket['winner_position'],
                    'payout_amount' => $winnerTicket['payout_amount'] ?? null,
                    'payout_currency_id' => $winnerTicket['payout_currency_id'] ?? null,
                ];
            }
        }

        // Выполняем массовую вставку новых записей
        if (!empty($insertData)) {
            $log->info("Добавили в базу новые выигрышные билеты", ['count' => count($insertData)]);
            WinnerTicket::insert($insertData);
        } else {
            $log->info("Новых выигрышных билетов для добавления нет");
        }
    }

    public function canDrawLottery(int $lotteryId): bool
    {
        // TODO: Нужно ли учитывать lottery_schedules что бы дать признак розыгрыша (нужно релизовать)
        // (что бы раньше времени не разыгрывать)
        // если да, то нужно сделать логику хранения в redis ? или повторной отправки после определенного времени *
        return LotteryNumber::where('id', $lotteryId)
            ->where('is_drawn', false)
            ->exists();
    }

    public function lotteryDrawn(int $lotteryId): void
    {
        LotteryNumber::where('id', $lotteryId)
            ->update(['is_drawn' => true]);
    }

    private function getWinnerTickets(array $ticketIds): array
    {
        return WinnerTicket::whereIn('user_ticket_purchase_id', $ticketIds)
            ->select('winner_position', 'payout_amount')
            ->orderBy('winner_position')
            ->get()
            ->toArray();
    }

    /**
     * {@inheritdoc}
     */
    public function getUserStatistics(int $userId): array
    {
        $statistics = UserTicketPurchase::where('user_id', $userId)
            ->selectRaw('
                COUNT(*) as total_tickets,
                COUNT(DISTINCT lottery_id) as total_lotteries,
                SUM(purchase_amount) as total_spent
            ')
            ->first();

        if (!$statistics) {
            return [
                'tickets_total' => 0,
                'tickets_active' => 0,
                'winnings_by_currency' => []
            ];
        }

        // Считаем количество активных билетов (билеты в лотереях с будущей датой розыгрыша)
        $activeTickets = UserTicketPurchase::where('user_id', $userId)
            ->join('lottery_numbers as lm', 'lm.id', '=', 'user_ticket_purchases.lottery_id')
            ->where('lm.draw_date', '>=', Carbon::now())
            ->count();

        // Группируем выигрыши по валютам
        $winningsByCurrency = WinnerTicket::where('user_id', $userId)
            ->join('payment_currencies as pc', 'pc.id', '=', 'winner_tickets.payout_currency_id')
            ->selectRaw('
                pc.code as currency_code,
                pc.name as currency_name,
                SUM(winner_tickets.payout_amount) as total_amount
            ')
            ->groupBy('pc.id', 'pc.code', 'pc.name')
            ->get()
            ->map(function ($item) {
                return [
                    'currency_code' => $item->currency_code,
                    'currency_name' => $item->currency_name,
                    'total_amount' => $item->total_amount
                ];
            })
            ->toArray();

        return [
            'tickets_total' => $statistics->total_tickets,
            'tickets_active' => $activeTickets,
            'winnings_by_currency' => $winningsByCurrency
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function markLotteriesScheduleExported(array $lotteryIds): void
    {
        if (empty($lotteryIds)) {
            return;
        }

        LotteryNumber::whereIn('id', $lotteryIds)
            ->update(['schedule_exported_at' => Carbon::now()]);
    }

    /**
     * {@inheritdoc}
     */
    public function markLotteryWinnersConfigExported(int $lotteryId): void
    {
        LotteryNumber::where('id', $lotteryId)
            ->update(['winners_config_exported_at' => Carbon::now()]);
    }

    /**
     * Устанавливает дату экспорта билетов
     */
    public function markTicketsExported(array $ticketIds): void
    {
        if (empty($ticketIds)) {
            return;
        }

        UserTicketPurchase::whereIn('id', $ticketIds)
            ->update(['tickets_exported_at' => Carbon::now()]);
    }

    /**
     * {@inheritdoc}
     */
    public function getLotteriesReadyForWinnersConfigExport(): array
    {
        return LotteryNumber::query()
            ->select('id', 'lottery_name', 'lottery_type_id', 'end_date', 'draw_date', 'calculated_winners_count')
            ->where('is_active', true)
            ->where('is_drawn', false)
            ->whereNotNull('calculated_winners_count')
            ->whereNull('winners_config_exported_at')
            ->with(['lotteryType:id,name'])
            ->orderBy('end_date', 'asc')
            ->get()
            ->map(function (LotteryNumber $lottery) {
                return [
                    'id' => $lottery->id,
                    'lottery_name' => $lottery->lottery_name,
                    'lottery_type' => $lottery->lotteryType->name,
                    'end_date' => $lottery->end_date,
                    'draw_date' => $lottery->draw_date,
                    'calculated_winners_count' => $lottery->calculated_winners_count,
                ];
            })
            ->toArray();
    }
}
