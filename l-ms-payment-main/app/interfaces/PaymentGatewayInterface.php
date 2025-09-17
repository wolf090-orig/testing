<?php

declare(strict_types=1);

namespace app\interfaces;

/**
 * Интерфейс для платежного шлюза
 * Позволяет легко менять реализацию (FPGate, другие шлюзы)
 */
interface PaymentGatewayInterface
{
    /**
     * Создать пополнение (PayIn)
     */
    public function createPayIn(array $data): array;

    /**
     * Создать выплату (PayOut)
     */
    public function createPayOut(array $data): array;

    /**
     * Получить статус транзакции
     */
    public function getStatus(string $transactionId): array;

    /**
     * Получить баланс
     */
    public function getBalance(): array;
}
