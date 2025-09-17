<?php

/**
 * Конфигурация платежного сервиса ms-payment
 * 
 * Содержит только настройки FPGate (PayIn/PayOut)
 */

return [

    // Шлюз по умолчанию
    'default_gateway' => getenv('PAYMENT_DEFAULT_GATEWAY', 'fpgate'),

    // FPGate - PayIn (пополнение) - LotteryT каскад вход
    'fpgate_payin' => [
        'base_url' => getenv('FPGATE_PAYIN_BASE_URL'),
        'token' => getenv('FPGATE_PAYIN_TOKEN'),
        'secret' => getenv('FPGATE_PAYIN_SECRET'),
        'timeout' => (int)getenv('FPGATE_TIMEOUT_SECONDS', 30),
        'callback_url' => getenv('FPGATE_PAYIN_CALLBACK_URL', 'https://ms-payment/api/v1/payments/fpgate/callback'),
        'methods' => [
            // Депозиты согласно документации FPGate
            'card' => 'b11d98ad-1e09-4e63-8c4a-f1d8a4b9ce3f', // Депозиты по карте
            'sbp' => '894387d7-b8b6-4dab-82ee-dd1106f7369e', // Депозиты по СБП
            'cross_border_sbp' => 'b46146af-e597-4409-b24c-43a53b16f026', // Трансграничные депозиты по СБП
        ]
    ],

    // FPGate - PayOut (вывод) - LotteryT PS выход  
    'fpgate_payout' => [
        'base_url' => getenv('FPGATE_PAYOUT_BASE_URL'),
        'token' => getenv('FPGATE_PAYOUT_TOKEN'),
        'secret' => getenv('FPGATE_PAYOUT_SECRET'),
        'timeout' => (int)getenv('FPGATE_TIMEOUT_SECONDS', 30),
        'callback_url' => getenv('FPGATE_PAYOUT_CALLBACK_URL', 'https://ms-payment/api/v1/payments/fpgate/callback'),
        'methods' => [
            // Выплаты согласно документации FPGate
            'card_payout' => 'b11d98ad-1e09-4e63-8c4a-f1d8a4b9ce3f', // Выплаты по карте
            'sbp_payout' => '894387d7-b8b6-4dab-82ee-dd1106f7369e', // Выплаты по СБП
            'cross_border_sbp_payout' => 'b46146af-e597-4409-b24c-43a53b16f026', // Трансграничные выплаты по СБП
        ]
    ],

    // Лимиты платежей в копейках
    'limits' => [
        'payin' => [
            'min_amount' => (int)getenv('PAYIN_MIN_AMOUNT', 10000), // 100 руб
            'max_amount' => (int)getenv('PAYIN_MAX_AMOUNT', 10000000), // 100,000 руб
        ],
        'payout' => [
            'min_amount' => (int)getenv('PAYOUT_MIN_AMOUNT', 50000), // 500 руб
            'max_amount' => (int)getenv('PAYOUT_MAX_AMOUNT', 5000000), // 50,000 руб
        ],
    ],

    // Настройки времени жизни платежей
    'expiry_minutes' => (int)getenv('PAYMENT_EXPIRY_MINUTES', 15),

    // Справочники FPGate
    'fpgate_banks' => include __DIR__ . '/fpgate_banks.php',
    'fpgate_errors' => include __DIR__ . '/fpgate_errors.php',
];
