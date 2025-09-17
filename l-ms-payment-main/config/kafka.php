<?php

/**
 * Конфигурация Kafka для ms-payment
 * 
 * Используется для публикации событий статусов платежей
 * в топик payment_status_v1 для ms-ticket-manager
 */

return [
    'default_consumer_timeout' => env('KAFKA_CONSUMER_DEFAULT_TIMEOUT', 2000),
    'default_produce_timeout_ms' => env('KAFKA_PRODUCE_DEFAULT_TIMEOUT', 3000),

    'payment_status_topic' => env('KAFKA_PAYMENT_STATUS_TOPIC'),

    'payments' => [
        'metadata_broker_list' => env('KAFKA_NLU_BROKER_LIST'),
        'bootstrap_servers' => env('KAFKA_NLU_BROKER_LIST'),
        'with_sassl' => env('KAFKA_NLU_WITH_SASL'),
        'sasl_options' => [
            'sasl_protocol' => env('KAFKA_NLU_SASL_PROTOCOL'),
            'sasl_mechanisms' => env('KAFKA_NLU_SASL_MECHANISMS'),
            'sasl_username' => env('KAFKA_NLU_SASL_USERNAME'),
            'sasl_password' => env('KAFKA_NLU_SASL_PASSWORD'),
        ],
        'consumer_group_id' => env('KAFKA_NLU_CONSUMER_GROUP_ID'),
    ],
];
