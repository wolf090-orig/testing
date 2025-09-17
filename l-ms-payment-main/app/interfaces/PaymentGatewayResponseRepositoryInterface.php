<?php

declare(strict_types=1);

namespace App\Interfaces;

interface PaymentGatewayResponseRepositoryInterface
{
    /**
     * Сохранить ответ от платежного шлюза
     */
    public function saveResponse(array $responseData): int;

    /**
     * Проверить есть ли успешный ответ с реквизитами для оплаты
     */
    public function hasPaymentDetails(string $internalOrderId): bool;

    /**
     * Получить успешный ответ с реквизитами для оплаты
     */
    public function getSuccessfulResponse(string $internalOrderId): ?array;

    /**
     * Получить последний ответ от шлюза для транзакции
     */
    public function getLastResponse(string $internalOrderId): ?array;

    /**
     * Получить все ответы для транзакции
     */
    public function getResponsesByOrderId(string $internalOrderId): array;

    /**
     * Найти ответ по external_transaction_id
     */
    public function findByExternalTransactionId(string $externalTransactionId): ?array;
}


