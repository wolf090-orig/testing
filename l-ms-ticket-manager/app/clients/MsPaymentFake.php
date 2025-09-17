<?php

namespace app\clients;

use app\classes\Interfaces\MsPaymentInterface;
use support\Log;

class MsPaymentFake implements MsPaymentInterface
{
    private static array $payments = [];

    /**
     * Создает пополнение (PayIn) - фейковая реализация
     */
    public function createPayIn(
        string $internalOrderId,
        int $userId,
        int $amount,
        string $currency,
        string $paymentMethod,
        array $details = [],
        ?array $receipt = null
    ): array {
        Log::info('MsPaymentFake: симуляция создания PayIn', [
            'internal_order_id' => $internalOrderId,
            'amount' => $amount,
            'currency' => $currency,
            'payment_method' => $paymentMethod
        ]);

        $externalTransactionId = 'fake_' . uniqid();

        // Симулируем успешное создание платежа
        $payment = [
            'internal_order_id' => $internalOrderId,
            'external_transaction_id' => $externalTransactionId,
            'amount' => $amount,
            'currency' => $currency,
            'payment_method' => $paymentMethod,
            'status' => 'processing',
            'user_id' => $userId,
            'payment_details' => [
                'redirect_url' => 'https://fake-payment.example.com/pay/' . $externalTransactionId,
                'qr_code' => 'https://fake-payment.example.com/qr/' . $externalTransactionId
            ],
            'expires_at' => date('Y-m-d H:i:s', time() + 900), // 15 минут
            'created_at' => date('Y-m-d H:i:s')
        ];

        // Сохраняем в статическом массиве для симуляции БД
        self::$payments[$internalOrderId] = $payment;

        return [
            'success' => true,
            'data' => [
                'payment' => $payment
            ]
        ];
    }



    /**
     * Очищает фейковые данные (для тестов)
     */
    public static function clearFakeData(): void
    {
        self::$payments = [];
    }

    /**
     * Устанавливает статус платежа (для тестов)
     */
    public static function setPaymentStatus(string $internalOrderId, string $status): void
    {
        if (isset(self::$payments[$internalOrderId])) {
            self::$payments[$internalOrderId]['status'] = $status;
            if ($status === 'success') {
                self::$payments[$internalOrderId]['completed_at'] = date('Y-m-d H:i:s');
            }
        }
    }

    /**
     * Получает все фейковые платежи (для тестов)
     */
    public static function getAllFakePayments(): array
    {
        return self::$payments;
    }
}
