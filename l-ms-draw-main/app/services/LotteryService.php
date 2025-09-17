<?php

namespace app\services;

use app\repository\LotteryRepositoryDB;

class LotteryService
{
    private LotteryRepositoryDB $lotteryRepository;

    public function __construct()
    {
        $this->lotteryRepository = new LotteryRepositoryDB();
    }

    /**
     * Сохранить расписание лотереи
     */
    public function saveLotterySchedule(array $scheduleData): void
    {
        $this->lotteryRepository->saveLotterySchedule($scheduleData);
    }

    /**
     * Обновить конфигурацию розыгрыша
     */
    public function updateDrawConfig(int $lotteryId, array $configData): bool
    {
        return $this->lotteryRepository->updateDrawConfig($lotteryId, $configData);
    }
} 