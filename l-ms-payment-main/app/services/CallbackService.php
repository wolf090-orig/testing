<?php

declare(strict_types=1);

namespace app\services;

use app\interfaces\PaymentTransactionRepositoryInterface;
use app\interfaces\PaymentCallbackRepositoryInterface;
use support\Db;
use support\Log;
use Carbon\Carbon;
use Exception;

/**
 * Сервис для обработки callback'ов от платежных шлюзов
 */
class CallbackService
{
    private PaymentTransactionRepositoryInterface $transactionRepository;
    private PaymentCallbackRepositoryInterface $callbackRepository;

    public function __construct()
    {
        // Следуем паттерну из ticket-manager - получаем репозитории через DI Container
        $this->transactionRepository = \support\Container::get(PaymentTransactionRepositoryInterface::class);
        $this->callbackRepository = \support\Container::get(PaymentCallbackRepositoryInterface::class);
    }

    /**
     * Парсинг internal_order_id из callback order_id (убираем суффикс _0, _1, _2...)
     */
    public function parseInternalOrderId(string $callbackOrderId): string
    {
        Log::channel('payment_callback')->debug('Parsing internal order ID', [
            'callback_order_id' => $callbackOrderId
        ]);

        $parts = explode('_', $callbackOrderId);

        if (count($parts) < 2) {
            // Нет суффикса - возвращаем как есть
            return $callbackOrderId;
        }

        // Проверяем что последний элемент - число (суффикс попытки)
        $lastPart = end($parts);
        if (is_numeric($lastPart)) {
            // Убираем последний элемент (суффикс с номером попытки)
            array_pop($parts);
            
            // Склеиваем обратно
            $internalOrderId = implode('_', $parts);
            
            Log::channel('payment_callback')->debug('Order ID parsed successfully', [
                'callback_order_id' => $callbackOrderId,
                'internal_order_id' => $internalOrderId,
                'suffix' => $lastPart
            ]);
            
            return $internalOrderId;
        }

        // Последний элемент не число - возвращаем как есть
        Log::channel('payment_callback')->debug('Order ID has no numeric suffix', [
            'callback_order_id' => $callbackOrderId
        ]);
        
        return $callbackOrderId;
    }

    /**
     * Найти транзакцию по внутреннему order_id
     */
    public function findTransactionByInternalOrderId(string $internalOrderId): ?array
    {
        Log::channel('payment_callback')->debug('Finding transaction', [
            'internal_order_id' => $internalOrderId
        ]);

        $transaction = $this->transactionRepository->findByInternalOrderId($internalOrderId);

        if ($transaction) {
            Log::channel('payment_callback')->info('Transaction found', [
                'internal_order_id' => $internalOrderId,
                'transaction_id' => $transaction['id'],
                'status' => $transaction['status'],
                'payment_completed' => $transaction['payment_completed']
            ]);
        } else {
            Log::channel('payment_callback')->warning('Transaction not found', [
                'internal_order_id' => $internalOrderId
            ]);
        }

        return $transaction;
    }

    /**
     * Проверить существование дублирующего callback'а
     */
    public function findExistingCallback(string $externalTransactionId, string $callbackOrderId): ?array
    {
        Log::channel('payment_callback')->debug('Checking for duplicate callback', [
            'external_transaction_id' => $externalTransactionId,
            'callback_order_id' => $callbackOrderId
        ]);

        $existingCallback = $this->callbackRepository->findByTransactionAndOrderId(
            $externalTransactionId,
            $callbackOrderId
        );

        if ($existingCallback) {
            Log::channel('payment_callback')->info('Duplicate callback detected', [
                'callback_id' => $existingCallback['id'],
                'external_transaction_id' => $externalTransactionId,
                'callback_order_id' => $callbackOrderId
            ]);
        }

        return $existingCallback;
    }

    /**
     * Обработать callback - сохранить в БД и обновить статус транзакции
     */
    public function processCallback(array $validatedData, array $transaction, string $callbackOrderId): int
    {
        $internalOrderId = $transaction['internal_order_id'];

        Log::channel('payment_callback')->info('Processing callback', [
            'internal_order_id' => $internalOrderId,
            'external_transaction_id' => $validatedData['transaction_id'],
            'status_type' => $validatedData['status']['type'],
            'amount' => $validatedData['amount']['value']
        ]);

        // Подготавливаем данные callback для сохранения
        $callbackData = [
            'external_transaction_id' => $validatedData['transaction_id'],
            'order_id' => $callbackOrderId,
            'amount' => (float) $validatedData['amount']['value'],
            'currency' => $validatedData['amount']['currency'],
            'recalculated' => $validatedData['recalculated'] === 'true',
            'status_type' => $validatedData['status']['type'],
            'callback_timestamp' => $this->parseTimestamp($validatedData['timestamp']),
            'callback_data' => $validatedData
        ];

        // Выполняем операции в транзакции БД
        Db::beginTransaction();
        try {
            // 1. Сохраняем callback
            $callbackId = $this->callbackRepository->saveCallback($callbackData);

            Log::channel('payment_callback')->info('Callback saved to database', [
                'callback_id' => $callbackId,
                'internal_order_id' => $internalOrderId
            ]);

            // 2. Обновляем статус транзакции при необходимости
            if ($validatedData['status']['type'] === 'success') {
                // Платеж успешно завершен
                $this->transactionRepository->markPaymentCompleted($internalOrderId);
                
                Log::channel('payment_callback')->info('Payment marked as completed', [
                    'internal_order_id' => $internalOrderId,
                    'external_transaction_id' => $validatedData['transaction_id']
                ]);
            } elseif (in_array($validatedData['status']['type'], ['cancelled', 'error'])) {
                // Платеж отменен или ошибка
                $this->transactionRepository->updateStatus($internalOrderId, 'failed');
                
                Log::channel('payment_callback')->info('Payment marked as failed', [
                    'internal_order_id' => $internalOrderId,
                    'status_type' => $validatedData['status']['type']
                ]);
            }

            Db::commit();

            Log::channel('payment_callback')->info('Callback processing completed successfully', [
                'internal_order_id' => $internalOrderId,
                'callback_id' => $callbackId,
                'status_type' => $validatedData['status']['type']
            ]);

            return $callbackId;

        } catch (Exception $e) {
            Db::rollBack();
            
            Log::channel('payment_callback')->error('Callback transaction failed', [
                'internal_order_id' => $internalOrderId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Парсинг timestamp из callback
     */
    private function parseTimestamp(string $timestamp): Carbon
    {
        try {
            // Пробуем разные форматы timestamp
            return Carbon::parse($timestamp);
        } catch (Exception $e) {
            Log::channel('payment_callback')->warning('Failed to parse timestamp, using current time', [
                'timestamp' => $timestamp,
                'error' => $e->getMessage()
            ]);
            
            return Carbon::now();
        }
    }
}


