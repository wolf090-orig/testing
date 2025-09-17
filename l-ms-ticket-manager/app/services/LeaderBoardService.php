<?php

namespace app\services;

use app\model\LotteryNumber;
use app\model\PrizeConfiguration;
use app\model\PrizeConfigurationPosition;
use app\model\PaymentCurrency;
use Exception;
use support\Db;

class LeaderBoardService
{
    public int $lotteryNumberId;
    public LotteryNumber $lottery;
    public PrizeConfiguration $prizeConfig;

    public float $totalRevenue = 0;
    public float $prizePool = 0;
    public int $paidTicketsCount = 0;
    public int $participantsCount = 0;

    public function __construct(int $lotteryId)
    {
        $this->lotteryNumberId = $lotteryId;
        
        // Загружаем лотерею с конфигурацией призов
        $this->lottery = LotteryNumber::with(['prizeConfiguration.positions'])->findOrFail($lotteryId);
        
        if (!$this->lottery->prizeConfiguration) {
            throw new Exception("Для лотереи {$lotteryId} не найдена конфигурация призов");
        }
        
        $this->prizeConfig = $this->lottery->prizeConfiguration;
    }

    /**
     * Получает информацию о возможном выигрыше в лотерее на основе конфигурации из БД.
     *
     * @return array
     * @throws Exception
     */
    public function getLeaderBoard(): array
    {
        // Рассчитываем базовые показатели
        $this->calculateBasicStats();
        
        // Получаем фиксированные позиции из конфигурации
        $prizeDetails = $this->calculateFixedPositions();
        
        // Добавляем динамические позиции если есть правила
        if ($this->prizeConfig->hasDynamicDistribution()) {
            $dynamicPositions = $this->calculateDynamicPositions();
            $prizeDetails = array_merge($prizeDetails, $dynamicPositions);
        }
        
        return [
            'paid_tickets_count' => $this->paidTicketsCount,
            'players_quantity' => $this->participantsCount,
            'prize_fund' => $this->prizePool,
            'prize_details' => $prizeDetails,
        ];
    }

    /**
     * Рассчитывает базовые статистические показатели лотереи
     */
    private function calculateBasicStats(): void
    {
        $ticketPrice = $this->lottery->getPrice();

        // Получаем количество проданных билетов
        $this->paidTicketsCount = Db::table('user_ticket_purchases')
            ->where('lottery_id', $this->lotteryNumberId)
            ->count();

        // Получаем количество уникальных участников
        $this->participantsCount = Db::table('user_ticket_purchases')
            ->where('lottery_id', $this->lotteryNumberId)
            ->distinct('user_id')
            ->count('user_id');

        // Рассчитываем общую выручку и призовой фонд
        $this->totalRevenue = $ticketPrice * $this->paidTicketsCount;
        $this->prizePool = round($this->totalRevenue * ($this->prizeConfig->prize_fund_percentage / 100));
    }

    /**
     * Рассчитывает фиксированные позиции призов из конфигурации
     *
     * @return array
     */
    private function calculateFixedPositions(): array
    {
        $result = [];
        
        foreach ($this->prizeConfig->positions as $position) {
            $amount = 0;
            
            if ($position->isMoneyPrize()) {
                // Фиксированная денежная сумма
                $amount = $position->prize_amount_rub;
            } elseif ($position->isPercentagePrize()) {
                // Процент от призового фонда
                $amount = round($this->prizePool * ($position->prize_percentage / 100));
            }
            
            if ($amount > 0) {
                $result[] = [
                    'position' => $position->position,
                    'amount' => $amount
                ];
            }
        }
        
        return $result;
    }

    /**
     * Рассчитывает динамические позиции призов на основе правил
     *
     * @return array
     */
    private function calculateDynamicPositions(): array
    {
        $rules = $this->prizeConfig->getDynamicRules();
        if (!$rules) {
            return [];
        }
        
        // Рассчитываем остаток фонда для динамического распределения
        $dynamicFund = round($this->prizePool * ($rules['base_fund_percentage'] / 100));
        
        $positions = [];
        $currentPosition = $rules['after_position'] + 1;
        $currentPercentage = $rules['start_percentage'];
        $minAmount = $rules['min_amount_rub'];
        $decreaseStep = $rules['decrease_step'];
        
        while (true) {
            $amount = round($dynamicFund * ($currentPercentage / 100));
            
            // Проверяем стоп-условие
            if ($amount < $minAmount) {
                break;
            }
            
            $positions[] = [
                'position' => $currentPosition,
                'amount' => $amount
            ];
            
            $currentPosition++;
            $currentPercentage -= $decreaseStep;
            
            // Защита от бесконечного цикла
            if ($currentPercentage <= 0 || $currentPosition > 100) {
                break;
            }
        }
        
        return $positions;
    }

    /**
     * Устанавливает ID лотереи и перезагружает конфигурацию
     *
     * @param int $lotteryNumberId
     * @throws Exception
     */
    public function setLotteryNumberId(int $lotteryNumberId): void
    {
        $this->lotteryNumberId = $lotteryNumberId;
        
        // Перезагружаем лотерею с конфигурацией
        $this->lottery = LotteryNumber::with(['prizeConfiguration.positions'])->findOrFail($lotteryNumberId);
        
        if (!$this->lottery->prizeConfiguration) {
            throw new Exception("Для лотереи {$lotteryNumberId} не найдена конфигурация призов");
        }
        
        $this->prizeConfig = $this->lottery->prizeConfiguration;
    }
}
