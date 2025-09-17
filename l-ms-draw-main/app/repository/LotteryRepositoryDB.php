<?php

namespace app\repository;

use app\classes\Interfaces\LotteryRepositoryInterface;
use app\model\LotterySchedule;
use app\model\LotteryNumber;
use Carbon\Carbon;
use support\Db;
use Exception;

class LotteryRepositoryDB implements LotteryRepositoryInterface
{
    public $id;
    public $lottery_name;
    public $lottery_id;
    public $draw_date;
    public $end_date;
    public $drawn_at;

    public function __construct($data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->lottery_name = $data['lottery_name'] ?? null;
        $this->lottery_id = $data['lottery_id'] ?? null;
        $this->draw_date = $data['draw_date'] ?? null;
        $this->end_date = $data['end_date'] ?? null;
        $this->drawn_at = $data['drawn_at'] ?? null;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function findLottery(int $lotteryId): self
    {
        $lottery = LotterySchedule::where('lottery_id', $lotteryId)
            ->firstOrFail();
        return new LotteryRepositoryDB($lottery);
    }

    public function lotteryDrawed(): void
    {
        if (!is_null($this->id)) {
            LotterySchedule::where('lottery_id', $this->lottery_id)
                ->update([
                    'drawn_at' => Carbon::now(),
                ]);
        }
    }

    public function formatPublic(array $winnerTickets): array
    {

        /*
         * {
             "lottery_id": 6,
             "lottery_name": "string",
             "draw_date": "string",
             "tickets": [
              {
               "ticket_number": 7555007,
               "winner_position": 1
              },
              {
               "ticket_number": 5686946,
               "winner_position": 2
              }
             ]
            }
         * */

        $tickets = [];
        foreach ($winnerTickets as $ticket) {
            $tickets[] = [
                'ticket_number' => $ticket['ticket_number'],
                'winner_position' => $ticket['winner_position']
            ];
        }

        return [
            "lottery_id" => $this->lottery_id,
            "lottery_name" => $this->lottery_name,
            "draw_date" => $this->draw_date,
            "tickets" => $tickets
        ];
    }

    public function saveLotterySchedule(array $scheduleData): void
    {
        Db::table('lottery_numbers')->insertOrIgnore([
            'id' => $scheduleData['id'],
            'lottery_type' => $scheduleData['lottery_type'],
            'lottery_name' => $scheduleData['lottery_name'],
            'start_date' => $scheduleData['sale_start_date'],
            'end_date' => $scheduleData['sale_end_date'],
            'draw_date' => $scheduleData['draw_date'],
            'drawn_at' => null,
            'is_active' => true,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }

    public function updateDrawConfig(int $lotteryId, array $configData): bool
    {
        $updated = Db::table('lottery_numbers')
            ->where('id', $lotteryId)
            ->update([
                'calculated_winners_count' => $configData['calculated_winners_count'],
                'total_participants' => $configData['total_participants'] ?? null,
                'total_tickets_sold' => $configData['total_tickets_sold'] ?? null,
                'updated_at' => Carbon::now(),
            ]);

        if (!$updated) {
            throw new Exception("Лотерея с ID {$lotteryId} не найдена");
        }

        return true;
    }

    /**
     * Получить лотереи готовые к розыгрышу
     * Условия:
     * 1. Время розыгрыша наступило (draw_date < now)
     * 2. Розыгрыш еще не проведен (drawn_at IS NULL)
     * 3. Лотерея активна (is_active = true)
     * 4. Есть конфигурация победителей (calculated_winners_count IS NOT NULL)
     */
    public function getLotteries2Draw(): array
    {
        return LotteryNumber::where('draw_date', '<', Carbon::now())
            ->whereNull('drawn_at')
            ->where('is_active', true)
            ->whereNotNull('calculated_winners_count')
            ->get()
            ->toArray();
    }

    /**
     * Найти лотерею по ID
     */
    public function findById(int $lotteryId): ?array
    {
        $lottery = LotteryNumber::find($lotteryId);
        return $lottery ? $lottery->toArray() : null;
    }

    /**
     * Отметить лотерею как разыгранную
     */
    public function markAsDrawn(int $lotteryId): bool
    {
        $updated = LotteryNumber::where('id', $lotteryId)
            ->update(['drawn_at' => Carbon::now()]);
            
        return $updated > 0;
    }

    /**
     * Получить разыгранные лотереи
     */
    public function getDrawnLotteries(): array
    {
        return LotteryNumber::whereNotNull('drawn_at')
            ->where('is_active', true)
            ->get()
            ->toArray();
    }

    /**
     * Отметить результаты лотереи как экспортированные
     */
    public function markResultsAsExported(int $lotteryId): bool
    {
        $updated = LotteryNumber::where('id', $lotteryId)
            ->update(['results_exported_at' => Carbon::now()]);
            
        return $updated > 0;
    }

    /**
     * Получить разыгранные лотереи с неэкспортированными результатами
     */
    public function getDrawnLotteriesWithUnexportedResults(): array
    {
        return LotteryNumber::whereNotNull('drawn_at')
            ->whereNull('results_exported_at')
            ->where('is_active', true)
            ->get()
            ->toArray();
    }
}
