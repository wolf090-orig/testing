<?php

declare(strict_types=1);

namespace app\repository;

use App\Interfaces\PaymentCallbackRepositoryInterface;
use support\Db;
use Carbon\Carbon;
use Exception;
use support\Log;

/**
 * Репозиторий для работы с callback'ами платежей
 */
class PaymentCallbackRepository implements PaymentCallbackRepositoryInterface
{
    /**
     * Сохранить callback от платежного шлюза
     */
    public function saveCallback(array $callbackData): int
    {
        try {
            $data = [
                'external_transaction_id' => $callbackData['external_transaction_id'],
                'order_id' => $callbackData['order_id'],
                'amount' => $callbackData['amount'],
                'currency' => $callbackData['currency'],
                'recalculated' => $callbackData['recalculated'],
                'status_type' => $callbackData['status_type'],
                'callback_timestamp' => $callbackData['callback_timestamp'],
                'callback_data' => json_encode($callbackData['callback_data']),
                'processed' => false,
                'retry_count' => 0,
                'created_at' => Carbon::now(),
            ];

            return Db::table('payment_callbacks')->insertGetId($data);
        } catch (Exception $e) {
            Log::error('Failed to save payment callback', [
                'data' => $callbackData,
                'error' => $e->getMessage()
            ]);
            throw new Exception('Failed to save payment callback: ' . $e->getMessage());
        }
    }

    /**
     * Получить необработанные callback'и для отправки в Kafka
     */
    public function getUnprocessedCallbacks(int $limit = 100): array
    {
        try {
            return Db::table('payment_callbacks')
                ->where('processed', false)
                ->orderBy('created_at', 'asc')
                ->limit($limit)
                ->get()
                ->toArray();
        } catch (Exception $e) {
            Log::error('Failed to get unprocessed callbacks', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Отметить callback'и как обработанные
     */
    public function markAsProcessed(array $callbackIds): bool
    {
        try {
            $affected = Db::table('payment_callbacks')
                ->whereIn('id', $callbackIds)
                ->update([
                    'processed' => true,
                    'processed_at' => Carbon::now(),
                ]);

            return $affected > 0;
        } catch (Exception $e) {
            Log::error('Failed to mark callbacks as processed', [
                'callback_ids' => $callbackIds,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Увеличить счетчик попыток повторной отправки
     */
    public function incrementRetryCount(int $callbackId): bool
    {
        try {
            $affected = Db::table('payment_callbacks')
                ->where('id', $callbackId)
                ->increment('retry_count');

            return $affected > 0;
        } catch (Exception $e) {
            Log::error('Failed to increment retry count', [
                'callback_id' => $callbackId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Получить callback'и требующие повторной обработки
     */
    public function getCallbacksForRetry(int $maxRetries = 3, int $limit = 100): array
    {
        try {
            return Db::table('payment_callbacks')
                ->where('processed', false)
                ->where('retry_count', '<', $maxRetries)
                ->where('created_at', '<', Carbon::now()->subMinutes(1)) // Retry after 1 minute
                ->orderBy('retry_count', 'asc')
                ->orderBy('created_at', 'asc')
                ->limit($limit)
                ->get()
                ->toArray();
        } catch (Exception $e) {
            Log::error('Failed to get callbacks for retry', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Найти callback по внешнему ID транзакции и order_id
     */
    public function findByTransactionAndOrderId(string $externalTransactionId, string $orderId): ?array
    {
        try {
            $result = Db::table('payment_callbacks')
                ->where('external_transaction_id', $externalTransactionId)
                ->where('order_id', $orderId)
                ->first();

            return $result ? (array) $result : null;
        } catch (Exception $e) {
            Log::error('Failed to find callback by transaction and order ID', [
                'external_transaction_id' => $externalTransactionId,
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}


