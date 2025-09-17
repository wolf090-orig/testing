<?php

declare(strict_types=1);

namespace app\interfaces;

interface PaymentCallbackRepositoryInterface
{
    /**
     * Сохранить callback от платежного шлюза
     */
    public function saveCallback(array $callbackData): int;

    /**
     * Получить необработанные callback'и для отправки в Kafka
     */
    public function getUnprocessedCallbacks(int $limit = 100): array;

    /**
     * Отметить callback'и как обработанные
     */
    public function markAsProcessed(array $callbackIds): bool;

    /**
     * Увеличить счетчик попыток повторной отправки
     */
    public function incrementRetryCount(int $callbackId): bool;

    /**
     * Получить callback'и требующие повторной обработки
     */
    public function getCallbacksForRetry(int $maxRetries = 3, int $limit = 100): array;

    /**
     * Найти callback по внешнему ID транзакции и order_id
     */
    public function findByTransactionAndOrderId(string $externalTransactionId, string $orderId): ?array;
}


