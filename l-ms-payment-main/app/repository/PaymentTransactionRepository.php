<?php

declare(strict_types=1);

namespace app\repository;

use app\interfaces\PaymentTransactionRepositoryInterface;
use support\Db;
use Carbon\Carbon;
use Exception;
use support\Log;

/**
 * Репозиторий для работы с транзакциями платежей
 */
class PaymentTransactionRepository implements PaymentTransactionRepositoryInterface
{
    /**
     * Создать новую транзакцию
     */
    public function createTransaction(array $transactionData): array
    {
        try {
            $data = [
                'internal_order_id' => $transactionData['internal_order_id'],
                'transaction_type' => $transactionData['transaction_type'],
                'user_id' => $transactionData['user_id'],
                'amount' => $transactionData['amount'],
                'currency' => $transactionData['currency'],
                'payment_method' => $transactionData['payment_method'],
                'status' => 'created',
                'payment_completed' => false,
                'gateway_request_attempts' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];

            $id = Db::table('payment_transactions')->insertGetId($data);
            
            return array_merge($data, ['id' => $id]);
        } catch (Exception $e) {
            Log::error('Failed to create payment transaction', [
                'data' => $transactionData,
                'error' => $e->getMessage()
            ]);
            throw new Exception('Failed to create payment transaction: ' . $e->getMessage());
        }
    }

    /**
     * Найти транзакцию по внутреннему ID заказа
     */
    public function findByInternalOrderId(string $internalOrderId): ?array
    {
        try {
            $result = Db::table('payment_transactions')
                ->where('internal_order_id', $internalOrderId)
                ->first();

            return $result ? (array) $result : null;
        } catch (Exception $e) {
            Log::error('Failed to find payment transaction', [
                'internal_order_id' => $internalOrderId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Получить транзакции для проверки статуса (без завершения платежа)
     */
    public function getTransactionsForStatusCheck(int $limit = 1): array
    {
        try {
            $oneMinuteAgo = Carbon::now()->subMinute();
            
            return Db::table('payment_transactions')
                ->where('payment_completed', false)
                ->where('created_at', '>', $oneMinuteAgo)
                ->whereNotNull('external_transaction_id')
                ->orderBy('last_status_check', 'asc')
                ->limit($limit)
                ->get()
                ->toArray();
        } catch (Exception $e) {
            Log::error('Failed to get transactions for status check', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Отметить платеж как завершенный
     */
    public function markPaymentCompleted(string $internalOrderId): bool
    {
        try {
            $affected = Db::table('payment_transactions')
                ->where('internal_order_id', $internalOrderId)
                ->update([
                    'payment_completed' => true,
                    'status' => 'success',
                    'updated_at' => Carbon::now(),
                ]);

            return $affected > 0;
        } catch (Exception $e) {
            Log::error('Failed to mark payment as completed', [
                'internal_order_id' => $internalOrderId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Обновить время последней проверки статуса
     */
    public function updateLastStatusCheck(string $internalOrderId): bool
    {
        try {
            $affected = Db::table('payment_transactions')
                ->where('internal_order_id', $internalOrderId)
                ->update([
                    'last_status_check' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);

            return $affected > 0;
        } catch (Exception $e) {
            Log::error('Failed to update last status check', [
                'internal_order_id' => $internalOrderId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Увеличить счетчик попыток запросов к шлюзу
     */
    public function incrementGatewayAttempts(string $internalOrderId): bool
    {
        try {
            $affected = Db::table('payment_transactions')
                ->where('internal_order_id', $internalOrderId)
                ->increment('gateway_request_attempts', 1, [
                    'updated_at' => Carbon::now()
                ]);

            return $affected > 0;
        } catch (Exception $e) {
            Log::error('Failed to increment gateway attempts', [
                'internal_order_id' => $internalOrderId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Обновить external_transaction_id
     */
    public function updateExternalTransactionId(string $internalOrderId, string $externalTransactionId): bool
    {
        try {
            $affected = Db::table('payment_transactions')
                ->where('internal_order_id', $internalOrderId)
                ->update([
                    'external_transaction_id' => $externalTransactionId,
                    'updated_at' => Carbon::now(),
                ]);

            return $affected > 0;
        } catch (Exception $e) {
            Log::error('Failed to update external transaction ID', [
                'internal_order_id' => $internalOrderId,
                'external_transaction_id' => $externalTransactionId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Обновить статус транзакции
     */
    public function updateStatus(string $internalOrderId, string $status): bool
    {
        try {
            $affected = Db::table('payment_transactions')
                ->where('internal_order_id', $internalOrderId)
                ->update([
                    'status' => $status,
                    'updated_at' => Carbon::now(),
                ]);

            return $affected > 0;
        } catch (Exception $e) {
            Log::error('Failed to update transaction status', [
                'internal_order_id' => $internalOrderId,
                'status' => $status,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}


