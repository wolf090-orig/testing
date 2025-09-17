<?php

declare(strict_types=1);

namespace App\Interfaces;

interface PaymentTransactionRepositoryInterface
{
    /**
     * Создать новую транзакцию
     */
    public function createTransaction(array $transactionData): array;

    /**
     * Найти транзакцию по внутреннему ID заказа
     */
    public function findByInternalOrderId(string $internalOrderId): ?array;

    /**
     * Получить транзакции для проверки статуса (без завершения платежа)
     */
    public function getTransactionsForStatusCheck(int $limit = 1): array;

    /**
     * Отметить платеж как завершенный
     */
    public function markPaymentCompleted(string $internalOrderId): bool;

    /**
     * Обновить время последней проверки статуса
     */
    public function updateLastStatusCheck(string $internalOrderId): bool;

    /**
     * Увеличить счетчик попыток запросов к шлюзу
     */
    public function incrementGatewayAttempts(string $internalOrderId): bool;

    /**
     * Обновить external_transaction_id
     */
    public function updateExternalTransactionId(string $internalOrderId, string $externalTransactionId): bool;

    /**
     * Обновить статус транзакции
     */
    public function updateStatus(string $internalOrderId, string $status): bool;
}


