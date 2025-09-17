<?php

/**
 * This file is part of webman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link      http://www.workerman.net/
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */

use app\classes\Logging\CustomJsonFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

$stdOutHandler = [
    'class' => StreamHandler::class,
    'constructor' => [
        'php://stdout',
        Logger::DEBUG,
    ],
    'formatter' => [
        'class' => CustomJsonFormatter::class,
        'constructor' => [],
    ],
];

return [
    // Основной канал логирования webman приложения
    'default' => [
        'handlers' => [
            [
                'class' => Monolog\Handler\RotatingFileHandler::class,
                'constructor' => [
                    runtime_path() . '/logs/webman.log',
                    7, // $maxFiles
                    Logger::DEBUG,
                ],
                'formatter' => [
                    'class' => CustomJsonFormatter::class,
                    'constructor' => [],
                ],
            ],
            'stdout' => $stdOutHandler
        ],
    ],
    // Канал для логирования тестовых команд
    'command_test_log' => [
        'handlers' => [
            [
                'class' => RotatingFileHandler::class,
                'constructor' => [
                    runtime_path() . '/logs/command_test_log.log',
                    7, // $maxFiles
                    Logger::DEBUG,
                ],
                'formatter' => [
                    'class' => CustomJsonFormatter::class,
                    'constructor' => [],
                ],
            ],
            'stdout' => $stdOutHandler
        ],
    ],
    // Канал для логирования команды закрытия корзины
    'command_basket_close' => [
        'handlers' => [
            [
                'class' => RotatingFileHandler::class,
                'constructor' => [
                    runtime_path() . '/logs/command_basket_close.log',
                    7, // $maxFiles
                    Logger::DEBUG,
                ],
                'formatter' => [
                    'class' => CustomJsonFormatter::class,
                    'constructor' => [],
                ],
            ],
            'stdout' => $stdOutHandler
        ],
    ],
    // Канал для логирования экспорта активных лотерей
    'command_export_active_lotteries' => [
        'handlers' => [
            [
                'class' => RotatingFileHandler::class,
                'constructor' => [
                    runtime_path() . '/logs/command_export_active_lotteries.log',
                    7, // $maxFiles
                    Logger::DEBUG,
                ],
                'formatter' => [
                    'class' => CustomJsonFormatter::class,
                    'constructor' => [],
                ],
            ],
            'stdout' => $stdOutHandler
        ],
    ],
    // Канал для логирования экспорта ежедневных билетов с фиксированным призом
    'command_export_daily_fixed_tickets' => [
        'handlers' => [
            [
                'class' => RotatingFileHandler::class,
                'constructor' => [
                    runtime_path() . '/logs/command_export_daily_fixed_tickets.log',
                    7, // $maxFiles
                    Logger::DEBUG,
                ],
                'formatter' => [
                    'class' => CustomJsonFormatter::class,
                    'constructor' => [],
                ],
            ],
            'stdout' => $stdOutHandler
        ],
    ],
    // Канал для логирования экспорта ежедневных билетов с динамическим призом
    'command_export_daily_dynamic_tickets' => [
        'handlers' => [
            [
                'class' => RotatingFileHandler::class,
                'constructor' => [
                    runtime_path() . '/logs/command_export_daily_dynamic_tickets.log',
                    7, // $maxFiles
                    Logger::DEBUG,
                ],
                'formatter' => [
                    'class' => CustomJsonFormatter::class,
                    'constructor' => [],
                ],
            ],
            'stdout' => $stdOutHandler
        ],
    ],
    // Канал для логирования экспорта джекпот билетов
    'command_export_jackpot_tickets' => [
        'handlers' => [
            [
                'class' => RotatingFileHandler::class,
                'constructor' => [
                    runtime_path() . '/logs/command_export_jackpot_tickets.log',
                    7, // $maxFiles
                    Logger::DEBUG,
                ],
                'formatter' => [
                    'class' => CustomJsonFormatter::class,
                    'constructor' => [],
                ],
            ],
            'stdout' => $stdOutHandler
        ],
    ],
    // Канал для логирования экспорта супертур билетов
    'command_export_supertour_tickets' => [
        'handlers' => [
            [
                'class' => RotatingFileHandler::class,
                'constructor' => [
                    runtime_path() . '/logs/command_export_supertour_tickets.log',
                    7, // $maxFiles
                    Logger::DEBUG,
                ],
                'formatter' => [
                    'class' => CustomJsonFormatter::class,
                    'constructor' => [],
                ],
            ],
            'stdout' => $stdOutHandler
        ],
    ],
    // Канал для логирования импорта выигрышных билетов
    'command_import_winner_tickets' => [
        'handlers' => [
            [
                'class' => RotatingFileHandler::class,
                'constructor' => [
                    runtime_path() . '/logs/command_import_winner_tickets.log',
                    7, // $maxFiles
                    Logger::DEBUG,
                ],
                'formatter' => [
                    'class' => CustomJsonFormatter::class,
                    'constructor' => [],
                ],
            ],
            'stdout' => $stdOutHandler
        ],
    ],
    // Канал для логирования интеграции с ms-payment
    'ms_payment_integration' => [
        'handlers' => [
            [
                'class' => RotatingFileHandler::class,
                'constructor' => [
                    runtime_path() . '/logs/ms_payment_integration.log',
                    7, // $maxFiles
                    Logger::DEBUG,
                ],
                'formatter' => [
                    'class' => CustomJsonFormatter::class,
                    'constructor' => [],
                ],
            ],
            'stdout' => $stdOutHandler
        ],
    ],
    // Канал для логирования генерации лотерей
    'command_lottery_numbers_generator' => [
        'handlers' => [
            [
                'class' => RotatingFileHandler::class,
                'constructor' => [
                    runtime_path() . '/logs/command_lottery_numbers_generator.log',
                    7, // $maxFiles
                    Logger::DEBUG,
                ],
                'formatter' => [
                    'class' => CustomJsonFormatter::class,
                    'constructor' => [],
                ],
            ],
            'stdout' => $stdOutHandler
        ],
    ],
    // Канал для логирования генерации билетов
    'command_lottery_tickets_generator' => [
        'handlers' => [
            [
                'class' => RotatingFileHandler::class,
                'constructor' => [
                    runtime_path() . '/logs/command_lottery_tickets_generator.log',
                    7, // $maxFiles
                    Logger::DEBUG,
                ],
                'formatter' => [
                    'class' => CustomJsonFormatter::class,
                    'constructor' => [],
                ],
            ],
            'stdout' => $stdOutHandler
        ],
    ],
    // Канал для логирования автодогенерации билетов
    'auto_generate_tickets' => [
        'handlers' => [
            [
                'class' => RotatingFileHandler::class,
                'constructor' => [
                    runtime_path() . '/logs/auto_generate_tickets.log',
                    7, // $maxFiles
                    Logger::DEBUG,
                ],
                'formatter' => [
                    'class' => CustomJsonFormatter::class,
                    'constructor' => [],
                ],
            ],
            'stdout' => $stdOutHandler
        ],
    ],
    // Канал для логирования процесса автогенерации лотерей  
    'process_generate_lottery_numbers' => [
        'handlers' => [
            [
                'class' => RotatingFileHandler::class,
                'constructor' => [
                    runtime_path() . '/logs/process_generate_lottery_numbers.log',
                    30, // $maxFiles - больше файлов для отслеживания работы процесса
                    Logger::DEBUG,
                ],
                'formatter' => [
                    'class' => CustomJsonFormatter::class,
                    'constructor' => [],
                ],
            ],
            'stdout' => $stdOutHandler
        ],
    ],
    // Канал для логирования процесса автоматического закрытия корзин
    'process_close_baskets' => [
        'handlers' => [
            [
                'class' => RotatingFileHandler::class,
                'constructor' => [
                    runtime_path() . '/logs/process_close_baskets.log',
                    30, // $maxFiles - больше файлов для отслеживания работы процесса
                    Logger::DEBUG,
                ],
                'formatter' => [
                    'class' => CustomJsonFormatter::class,
                    'constructor' => [],
                ],
            ],
            'stdout' => $stdOutHandler
        ],
    ],
    // Канал для логирования процесса автогенерации билетов
    'process_generate_tickets' => [
        'handlers' => [
            [
                'class' => RotatingFileHandler::class,
                'constructor' => [
                    runtime_path() . '/logs/process_generate_tickets.log',
                    30, // $maxFiles - больше файлов для отслеживания работы процесса
                    Logger::DEBUG,
                ],
                'formatter' => [
                    'class' => CustomJsonFormatter::class,
                    'constructor' => [],
                ],
            ],
            'stdout' => $stdOutHandler
        ],
    ],
    // Канал для логирования процесса экспорта активных лотерей
    'process_export_active_lotteries' => [
        'handlers' => [
            [
                'class' => RotatingFileHandler::class,
                'constructor' => [
                    runtime_path() . '/logs/process_export_active_lotteries.log',
                    30, // $maxFiles - больше файлов для отслеживания работы процесса
                    Logger::DEBUG,
                ],
                'formatter' => [
                    'class' => CustomJsonFormatter::class,
                    'constructor' => [],
                ],
            ],
            'stdout' => $stdOutHandler
        ],
    ],
    // Канал для логирования процесса экспорта билетов
    'process_export_tickets' => [
        'handlers' => [
            [
                'class' => RotatingFileHandler::class,
                'constructor' => [
                    runtime_path() . '/logs/process_export_tickets.log',
                    30, // $maxFiles - больше файлов для отслеживания работы процесса
                    Logger::DEBUG,
                ],
                'formatter' => [
                    'class' => CustomJsonFormatter::class,
                    'constructor' => [],
                ],
            ],
            'stdout' => $stdOutHandler
        ],
    ],
    // Канал для логирования команды расчета количества победителей
    'command_calculate_lottery_winners' => [
        'handlers' => [
            [
                'class' => RotatingFileHandler::class,
                'constructor' => [
                    runtime_path() . '/logs/command_calculate_lottery_winners.log',
                    7, // $maxFiles
                    Logger::DEBUG,
                ],
                'formatter' => [
                    'class' => CustomJsonFormatter::class,
                    'constructor' => [],
                ],
            ],
            'stdout' => $stdOutHandler
        ],
    ],
    // Канал для логирования процесса расчета количества победителей
    'process_calculate_lottery_winners' => [
        'handlers' => [
            [
                'class' => RotatingFileHandler::class,
                'constructor' => [
                    runtime_path() . '/logs/process_calculate_lottery_winners.log',
                    30, // $maxFiles - больше файлов для отслеживания работы процесса
                    Logger::DEBUG,
                ],
                'formatter' => [
                    'class' => CustomJsonFormatter::class,
                    'constructor' => [],
                ],
            ],
            'stdout' => $stdOutHandler
        ],
    ],
    // Канал для логирования команды экспорта конфигурации победителей
    'command_export_lottery_winners_config' => [
        'handlers' => [
            [
                'class' => RotatingFileHandler::class,
                'constructor' => [
                    runtime_path() . '/logs/command_export_lottery_winners_config.log',
                    7, // $maxFiles
                    Logger::DEBUG,
                ],
                'formatter' => [
                    'class' => CustomJsonFormatter::class,
                    'constructor' => [],
                ],
            ],
            'stdout' => $stdOutHandler
        ],
    ],
    // Канал для логирования процесса экспорта конфигурации победителей
    'process_export_lottery_winners_config' => [
        'handlers' => [
            [
                'class' => RotatingFileHandler::class,
                'constructor' => [
                    runtime_path() . '/logs/process_export_lottery_winners_config.log',
                    30, // $maxFiles - больше файлов для отслеживания работы процесса
                    Logger::DEBUG,
                ],
                'formatter' => [
                    'class' => CustomJsonFormatter::class,
                    'constructor' => [],
                ],
            ],
            'stdout' => $stdOutHandler
        ],
    ],
    // Канал для логирования процесса импорта выигрышных билетов
    'process_import_winner_tickets' => [
        'handlers' => [
            [
                'class' => RotatingFileHandler::class,
                'constructor' => [
                    runtime_path() . '/logs/process_import_winner_tickets.log',
                    30, // $maxFiles - больше файлов для отслеживания работы процесса
                    Logger::DEBUG,
                ],
                'formatter' => [
                    'class' => CustomJsonFormatter::class,
                    'constructor' => [],
                ],
            ],
            'stdout' => $stdOutHandler
        ],
    ],
    // Канал для логирования Telegram авторизации
    'telegram_auth' => [
        'handlers' => [
            [
                'class' => Monolog\Handler\RotatingFileHandler::class,
                'constructor' => [
                    runtime_path() . '/logs/telegram_auth.log',
                    7, // Max files
                    Monolog\Logger::DEBUG,
                ],
                'formatter' => [
                    'class' => Monolog\Formatter\LineFormatter::class,
                    'constructor' => [null, 'Y-m-d H:i:s', true],
                ],
            ]
        ],
    ],
    // Канал для логирования отладочной информации с фронтенда
    'frontend_debug' => [
        'handlers' => [
            [
                'class' => Monolog\Handler\RotatingFileHandler::class,
                'constructor' => [
                    runtime_path() . '/logs/frontend_debug.log',
                    7,
                    \Monolog\Logger::DEBUG,
                ],
                'formatter' => [
                    'class' => Monolog\Formatter\LineFormatter::class,
                    'constructor' => [
                        null, // Формат по умолчанию
                        'Y-m-d H:i:s',
                        true,
                        true
                    ],
                ],
            ]
        ],
    ],
    // Канал для логирования HTTP запросов и ответов
    'request' => [
        'handlers' => [
            [
                'class' => RotatingFileHandler::class,
                'constructor' => [
                    runtime_path() . '/logs/request.log',
                    7, // $maxFiles
                    Logger::DEBUG,
                ],
                'formatter' => [
                    'class' => CustomJsonFormatter::class,
                    'constructor' => [],
                ],
            ],
            'stdout' => $stdOutHandler
        ],
    ],
];
