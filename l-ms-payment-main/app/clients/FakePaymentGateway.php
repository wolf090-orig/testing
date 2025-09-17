<?php

declare(strict_types=1);

namespace app\clients;

use app\interfaces\PaymentGatewayInterface;
use support\Log;

/**
 * Фейковая реализация платежного шлюза для тестирования
 * Имитирует различные сценарии ответов от FPGate для тестирования
 */
class FakePaymentGateway implements PaymentGatewayInterface
{
    private static array $transactions = [];
    
    // Режимы тестирования
    public const MODE_SUCCESS = 'success';           // Успешный ответ с реквизитами
    public const MODE_DUPLICATE = 'duplicate';       // Ошибка 1011 - дубликат заказа
    public const MODE_NO_CARDS = 'no_cards';        // Ошибка 1004 - нет реквизитов
    
    private static string $testMode = self::MODE_SUCCESS;

    /**
     * Установить режим тестирования
     */
    public static function setTestMode(string $mode): void
    {
        if (!in_array($mode, [self::MODE_SUCCESS, self::MODE_DUPLICATE, self::MODE_NO_CARDS])) {
            throw new \InvalidArgumentException("Invalid test mode: $mode");
        }
        self::$testMode = $mode;
        Log::info('FakePaymentGateway: режим изменен', ['mode' => $mode]);
    }

    /**
     * Получить текущий режим тестирования
     */
    public static function getTestMode(): string
    {
        return self::$testMode;
    }

    /**
     * Создать пополнение (PayIn) - основной метод с автоматическим определением режима
     */
    public function createPayIn(array $data): array
    {
        $orderId = $data['order_id'] ?? 'unknown';
        $amount = $data['amount']['value'] ?? 0;
        
        // Проверяем, есть ли уже транзакция с таким order_id
        if (isset(self::$transactions[$orderId])) {
            Log::info('FakePaymentGateway: возврат существующей транзакции', [
                'order_id' => $orderId,
                'existing_transaction_id' => self::$transactions[$orderId]['transaction_id'] ?? 'unknown'
            ]);
            
            return [
                'success' => true,
                'data' => self::$transactions[$orderId]
            ];
        }
        
        // Автоматически определяем режим на основе данных запроса
        $mode = $this->determineTestMode($orderId, (float)$amount);

        Log::info('FakePaymentGateway: симуляция создания PayIn', [
            'determined_mode' => $mode,
            'order_id' => $orderId,
            'amount' => $amount,
            'description' => $data['description'] ?? 'unknown'
        ]);

        // Переключаем поведение в зависимости от определенного режима
        switch ($mode) {
            case self::MODE_DUPLICATE:
                return $this->createPayInDuplicate($data);
            case self::MODE_NO_CARDS:
                return $this->createPayInNoCards($data);
            case self::MODE_SUCCESS:
            default:
                return $this->createPayInSuccess($data);
        }
    }

    /**
     * Определить режим тестирования на основе данных запроса
     * 
     * Логика приоритетов (проверки идут по порядку):
     * 1. Сумма 1000 рублей → MODE_NO_CARDS (ошибка 1004)
     * 2. Order_id содержит "basket_0" → MODE_DUPLICATE (ошибка 1011) 
     * 3. Все остальные случаи → MODE_SUCCESS (успешный ответ с реквизитами)
     */
    private function determineTestMode(string $orderId, float $amount): string
    {
        // Приоритет 1: Если сумма 1000 руб - возвращаем ошибку "нет реквизитов"
        if ($amount == 1000.0) {
            return self::MODE_NO_CARDS;
        }
        
        // Приоритет 2: Если order_id содержит "basket_0" - возвращаем ошибку дубликата
        if (str_contains($orderId, 'basket_0')) {
            return self::MODE_DUPLICATE;
        }
        
        // Приоритет 3: В остальных случаях - успех
        return self::MODE_SUCCESS;
    }

    /**
     * Успешный ответ с реквизитами карты
     */
    private function createPayInSuccess(array $data): array
    {
        $externalTransactionId = 'fake_payin_' . uniqid();

        $transaction = [
            'token' => 'fake_token_' . substr(md5($data['order_id']), 0, 8),
            'order_id' => $data['order_id'],
            'transaction_id' => $externalTransactionId,
            'type' => 'payin',
            'status' => [
                'type' => 'processing'
            ],
            'amount' => [
                'value' => $data['amount']['value'],
                'currency' => $data['amount']['currency'],
                'fee' => '0.00'
            ],
            'details' => [
                'name' => 'Тестовый Пользователь',
                'card' => '2200150000000000',
                'bank' => 'ТЕСТОВЫЙ БАНК'
            ],
            'is_fake' => true,
            'fake_client_version' => 'v1.0.0',
            'recalculated' => 'false',
            'description' => $data['description'],
            'creating_date' => gmdate('Y-m-d\TH:i:s\Z'),
            'expiring_date' => gmdate('Y-m-d\TH:i:s\Z', time() + 900), // 15 минут
            'callback_url' => $data['callback_url'] ?? null
        ];

        // Сохраняем в статическом массиве для имитации БД
        self::$transactions[$data['order_id']] = $transaction;

        return [
            'success' => true,
            'data' => $transaction
        ];
    }

    /**
     * Ошибка 1011 - транзакция с таким order_id уже существует
     */
    private function createPayInDuplicate(array $data): array
    {
        Log::info('FakePaymentGateway: симуляция ошибки дубликата 1011');

        $errorResponse = [
            'status' => [
                'type' => 'error',
                'error_code' => '1011',
                'error_description' => 'Transaction with this order_id already exists'
            ],
            'is_fake' => true,
            'fake_client_version' => 'v1.0.0'
        ];

        return [
            'success' => true, // HTTP 200, но с ошибкой в ответе
            'data' => $errorResponse
        ];
    }

    /**
     * Ошибка 1004 - нет доступных карт/реквизитов
     */
    private function createPayInNoCards(array $data): array
    {
        Log::info('FakePaymentGateway: симуляция ошибки отсутствия карт 1004');

        $externalTransactionId = 'fake_error_' . uniqid();

        $errorResponse = [
            'transaction_id' => $externalTransactionId,
            'status' => [
                'type' => 'error',
                'error_code' => '1004',
                'error_description' => 'No Cards / no phonenumbers'
            ],
            'is_fake' => true,
            'fake_client_version' => 'v1.0.0'
        ];

        return [
            'success' => true, // HTTP 200, но с ошибкой в ответе
            'data' => $errorResponse
        ];
    }

    /**
     * Создать выплату (PayOut)
     */
    public function createPayOut(array $data): array
    {
        Log::info('FakePaymentGateway: симуляция создания PayOut', [
            'order_id' => $data['order_id'] ?? 'unknown',
            'amount' => $data['amount'] ?? [],
            'description' => $data['description'] ?? 'unknown'
        ]);

        $externalTransactionId = 'fake_payout_' . uniqid();

        $transaction = [
            'token' => 'fake_token',
            'order_id' => $data['order_id'],
            'transaction_id' => $externalTransactionId,
            'type' => 'payout',
            'status' => [
                'type' => 'processing'
            ],
            'amount' => [
                'value' => $data['amount']['value'],
                'currency' => $data['amount']['currency'],
                'fee' => '0.00'
            ],
            'details' => $data['details'],
            'recalculated' => 'false',
            'description' => $data['description'],
            'creating_date' => date('Y-m-d\TH:i:s'),
            'callback_url' => $data['callback_url'] ?? null,
            'is_fake' => true,
            'fake_client_version' => 'v1.0.0'
        ];

        self::$transactions[$data['order_id']] = $transaction;

        return [
            'success' => true,
            'data' => $transaction
        ];
    }

    /**
     * Получить статус транзакции
     */
    public function getStatus(string $transactionId): array
    {
        Log::info('FakePaymentGateway: получение статуса', [
            'transaction_id' => $transactionId
        ]);

        // Ищем транзакцию по ID
        foreach (self::$transactions as $transaction) {
            if ($transaction['transaction_id'] === $transactionId) {
                // Случайно изменяем статус на success для имитации завершения
                if (rand(0, 1)) {
                    $transaction['status']['type'] = 'success';
                    $transaction['completing_date'] = date('Y-m-d\TH:i:s');
                }

                return [
                    'success' => true,
                    'data' => $transaction
                ];
            }
        }

        return [
            'success' => false,
            'error' => 'Transaction not found',
            'details' => 'Транзакция не найдена'
        ];
    }

    /**
     * Получить баланс
     */
    public function getBalance(): array
    {
        Log::info('FakePaymentGateway: получение баланса');

        return [
            'success' => true,
            'data' => [
                'token' => 'fake_token',
                'status' => [
                    'type' => 'success'
                ],
                'available_amount' => [
                    'value' => '1000000.00' // 1 миллион рублей для тестов
                ],
                'is_fake' => true,
                'fake_client_version' => 'v1.0.0'
            ]
        ];
    }

    /**
     * Симуляция успешного callback для тестов
     */
    public static function simulateCallback(string $orderId, string $status = 'success'): array
    {
        if (!isset(self::$transactions[$orderId])) {
            return [
                'success' => false,
                'error' => 'Transaction not found'
            ];
        }

        $transaction = self::$transactions[$orderId];
        
        $callback = [
            'token' => 'fake_token',
            'transaction_id' => $transaction['transaction_id'],
            'order_id' => $orderId,
            'amount' => $transaction['amount'],
            'recalculated' => 'false',
            'timestamp' => date('Y-m-d\TH:i:s'),
            'status' => [
                'type' => $status
            ],
            'signature' => 'fake_signature_' . md5($orderId . $status)
        ];

        Log::info('FakePaymentGateway: симуляция callback', $callback);

        return [
            'success' => true,
            'callback' => $callback
        ];
    }

    /**
     * Очистить фейковые данные (для тестов)
     */
    public static function clearTransactions(): void
    {
        self::$transactions = [];
        self::$testMode = self::MODE_SUCCESS; // Сбрасываем режим по умолчанию
    }

    /**
     * Вспомогательные методы для тестирования режимов
     */
    public static function enableSuccessMode(): void
    {
        self::setTestMode(self::MODE_SUCCESS);
    }

    public static function enableDuplicateMode(): void
    {
        self::setTestMode(self::MODE_DUPLICATE);
    }

    public static function enableNoCardsMode(): void
    {
        self::setTestMode(self::MODE_NO_CARDS);
    }
}
