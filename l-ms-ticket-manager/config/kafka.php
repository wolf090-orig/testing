<?php

return [
    'default_consumer_timeout' => env('KAFKA_CONSUMER_DEFAULT_TIMEOUT', 2000),
    'default_produce_timeout_ms' => env('KAFKA_PRODUCE_DEFAULT_TIMEOUT', 3000),

    'daily_fixed_tickets_topic' => env('KAFKA_NLU_TICKET_DAILY_FIXED_TOPIC'),
    'daily_dynamic_tickets_topic' => env('KAFKA_NLU_TICKET_DAILY_DYNAMIC_TOPIC'),
    'jackpot_tickets_topic' => env('KAFKA_NLU_TICKET_JACKPOT_TOPIC'),
    'supertour_tickets_topic' => env('KAFKA_NLU_TICKET_SUPERTOUR_TOPIC'),
    'lottery_schedules_topic' =>  env('KAFKA_NLU_LOTTERY_SCHEDULES_TOPIC'),
    'lottery_draw_results_topic' =>  env('KAFKA_NLU_LOTTERY_DRAW_RESULTS_TOPIC'),
    'lottery_draw_config_topic' => env('KAFKA_NLU_LOTTERY_DRAW_CONFIG_TOPIC'),

    'tickets' => [
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
