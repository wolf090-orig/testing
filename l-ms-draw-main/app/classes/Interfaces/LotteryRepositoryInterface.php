<?php

namespace app\classes\Interfaces;

interface LotteryRepositoryInterface
{
    public function getId(): ?int;

    public function findLottery(int $lotteryId): self;

    public function lotteryDrawed(): void;

    public function formatPublic(array $winnerTickets): array;

    /**
     * Сохранить расписание лотереи
     */
    public function saveLotterySchedule(array $scheduleData): void;

    /**
     * Обновить конфигурацию розыгрыша
     */
    public function updateDrawConfig(int $lotteryId, array $configData): bool;

    /**
     * Получить лотереи готовые к розыгрышу
     */
    public function getLotteries2Draw(): array;

    /**
     * Найти лотерею по ID
     */
    public function findById(int $lotteryId): ?array;

    /**
     * Отметить лотерею как разыгранную
     */
    public function markAsDrawn(int $lotteryId): bool;

    /**
     * Получить разыгранные лотереи
     */
    public function getDrawnLotteries(): array;

    /**
     * Отметить результаты лотереи как экспортированные
     */
    public function markResultsAsExported(int $lotteryId): bool;

    /**
     * Получить разыгранные лотереи с неэкспортированными результатами
     */
    public function getDrawnLotteriesWithUnexportedResults(): array;
}
