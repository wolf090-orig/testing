<?php

declare(strict_types=1);

namespace app\repository;

use app\interfaces\PaymentGatewayResponseRepositoryInterface;
use support\Db;
use Carbon\Carbon;
use Exception;
use support\Log;

/**
 * Репозиторий для работы с ответами платежного шлюза
 */
class PaymentGatewayResponseRepository implements PaymentGatewayResponseRepositoryInterface
{
    /**
     * Сохранить ответ от платежного шлюза
     */
    public function saveResponse(array $responseData): int
    {
        try {
            $data = [
                'internal_order_id' => $responseData['internal_order_id'],
                'external_transaction_id' => $responseData['external_transaction_id'] ?? null,
                'http_status_code' => $responseData['http_status_code'],
                'response_data' => json_encode($responseData['response_data']),
                'is_successful' => $responseData['is_successful'],
                'has_payment_details' => $responseData['has_payment_details'],
                'created_at' => Carbon::now(),
            ];

            return Db::table('payment_gateway_responses')->insertGetId($data);
        } catch (Exception $e) {
            Log::error('Failed to save gateway response', [
                'data' => $responseData,
                'error' => $e->getMessage()
            ]);
            throw new Exception('Failed to save gateway response: ' . $e->getMessage());
        }
    }

    /**
     * Проверить есть ли успешный ответ с реквизитами для оплаты
     */
    public function hasPaymentDetails(string $internalOrderId): bool
    {
        try {
            $response = Db::table('payment_gateway_responses')
                ->where('internal_order_id', $internalOrderId)
                ->where('is_successful', true)
                ->where('has_payment_details', true)
                ->first();

            if (!$response) {
                return false;
            }

            $responseData = json_decode($response->response_data, true);
            
            // КРИТИЧНО: Реквизиты есть ТОЛЬКО если есть данные карты
            // redirect_url НАМ НЕ ПОДХОДИТ!
            return isset($responseData['details']['card']) && !empty($responseData['details']['card']);
        } catch (Exception $e) {
            Log::error('Failed to check payment details', [
                'internal_order_id' => $internalOrderId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Получить успешный ответ с реквизитами для оплаты
     */
    public function getSuccessfulResponse(string $internalOrderId): ?array
    {
        try {
            $response = Db::table('payment_gateway_responses')
                ->where('internal_order_id', $internalOrderId)
                ->where('is_successful', true)
                ->where('has_payment_details', true)
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$response) {
                return null;
            }

            $responseData = json_decode($response->response_data, true);
            
            // Проверяем что есть реквизиты карты
            if (!isset($responseData['details']['card']) || empty($responseData['details']['card'])) {
                return null;
            }

            return (array) $response;
        } catch (Exception $e) {
            Log::error('Failed to get successful response', [
                'internal_order_id' => $internalOrderId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Получить последний ответ от шлюза для транзакции
     */
    public function getLastResponse(string $internalOrderId): ?array
    {
        try {
            $result = Db::table('payment_gateway_responses')
                ->where('internal_order_id', $internalOrderId)
                ->orderBy('created_at', 'desc')
                ->first();

            return $result ? (array) $result : null;
        } catch (Exception $e) {
            Log::error('Failed to get last response', [
                'internal_order_id' => $internalOrderId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Получить все ответы для транзакции
     */
    public function getResponsesByOrderId(string $internalOrderId): array
    {
        try {
            return Db::table('payment_gateway_responses')
                ->where('internal_order_id', $internalOrderId)
                ->orderBy('created_at', 'desc')
                ->get()
                ->toArray();
        } catch (Exception $e) {
            Log::error('Failed to get responses by order ID', [
                'internal_order_id' => $internalOrderId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Найти ответ по external_transaction_id
     */
    public function findByExternalTransactionId(string $externalTransactionId): ?array
    {
        try {
            $result = Db::table('payment_gateway_responses')
                ->where('external_transaction_id', $externalTransactionId)
                ->orderBy('created_at', 'desc')
                ->first();

            return $result ? (array) $result : null;
        } catch (Exception $e) {
            Log::error('Failed to find response by external transaction ID', [
                'external_transaction_id' => $externalTransactionId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}


