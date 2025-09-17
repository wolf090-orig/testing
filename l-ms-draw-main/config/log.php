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
    'default' => [
        'handlers' => [
            [
                'class' => Monolog\Handler\RotatingFileHandler::class,
                'constructor' => [
                    runtime_path() . '/logs/webman.log',
                    7, //$maxFiles
                    Monolog\Logger::DEBUG,
                ],
                'formatter' => [
                    'class' => Monolog\Formatter\LineFormatter::class,
                    'constructor' => [null, 'Y-m-d H:i:s', true],
                ],
            ],
            'stdout' => $stdOutHandler
        ],
    ],
    'basket_close_command' => [
        'handlers' => [
            [
                'class' => Monolog\Handler\RotatingFileHandler::class,
                'constructor' => [
                    runtime_path() . '/logs/basket_close_command.log',
                    7, //$maxFiles
                    Monolog\Logger::DEBUG,
                ],
                'formatter' => [
                    'class' => Monolog\Formatter\LineFormatter::class,
                    'constructor' => [null, 'Y-m-d H:i:s', true],
                ],
            ],
            'stdout' => $stdOutHandler
        ],
    ],
    'command_tickets_export_daily' => [
        'handlers' => [
            [
                'class' => Monolog\Handler\RotatingFileHandler::class,
                'constructor' => [
                    runtime_path() . '/logs/tickets_export_daily.log',
                    7, //$maxFiles
                    Monolog\Logger::DEBUG,
                ],
                'formatter' => [
                    'class' => Monolog\Formatter\LineFormatter::class,
                    'constructor' => [null, 'Y-m-d H:i:s', true],
                ],
            ],
            'stdout' => $stdOutHandler
        ],
    ],
    // Каналы для консьюмеров билетов
    'command_consume_daily_fixed_tickets' => [
        'handlers' => [
            [
                'class' => RotatingFileHandler::class,
                'constructor' => [
                    runtime_path() . '/logs/command_consume_daily_fixed_tickets.log',
                    7,
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
    'command_consume_daily_dynamic_tickets' => [
        'handlers' => [
            [
                'class' => RotatingFileHandler::class,
                'constructor' => [
                    runtime_path() . '/logs/command_consume_daily_dynamic_tickets.log',
                    7,
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
    'command_consume_jackpot_tickets' => [
        'handlers' => [
            [
                'class' => RotatingFileHandler::class,
                'constructor' => [
                    runtime_path() . '/logs/command_consume_jackpot_tickets.log',
                    7,
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
    'command_consume_supertour_tickets' => [
        'handlers' => [
            [
                'class' => RotatingFileHandler::class,
                'constructor' => [
                    runtime_path() . '/logs/command_consume_supertour_tickets.log',
                    7,
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
    // Каналы для системных консьюмеров
    'command_consume_schedules' => [
        'handlers' => [
            [
                'class' => RotatingFileHandler::class,
                'constructor' => [
                    runtime_path() . '/logs/command_consume_schedules.log',
                    7,
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
    'command_consume_draw_config' => [
        'handlers' => [
            [
                'class' => RotatingFileHandler::class,
                'constructor' => [
                    runtime_path() . '/logs/command_consume_draw_config.log',
                    7,
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
    // Каналы для процессов
    'process_draw_lottery' => [
        'handlers' => [
            [
                'class' => RotatingFileHandler::class,
                'constructor' => [
                    runtime_path() . '/logs/process_draw_lottery.log',
                    7,
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
    'process_consume_schedules' => [
        'handlers' => [
            [
                'class' => RotatingFileHandler::class,
                'constructor' => [
                    runtime_path() . '/logs/process_consume_schedules.log',
                    7,
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
    'process_consume_draw_config' => [
        'handlers' => [
            [
                'class' => RotatingFileHandler::class,
                'constructor' => [
                    runtime_path() . '/logs/process_consume_draw_config.log',
                    7,
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
    // Каналы для новых отдельных процессов консьюмеров билетов
    'process_consume_daily_fixed_tickets' => [
        'handlers' => [
            [
                'class' => RotatingFileHandler::class,
                'constructor' => [
                    runtime_path() . '/logs/process_consume_daily_fixed_tickets.log',
                    7,
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
    'process_consume_daily_dynamic_tickets' => [
        'handlers' => [
            [
                'class' => RotatingFileHandler::class,
                'constructor' => [
                    runtime_path() . '/logs/process_consume_daily_dynamic_tickets.log',
                    7,
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
    'process_consume_jackpot_tickets' => [
        'handlers' => [
            [
                'class' => RotatingFileHandler::class,
                'constructor' => [
                    runtime_path() . '/logs/process_consume_jackpot_tickets.log',
                    7,
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
    'process_consume_supertour_tickets' => [
        'handlers' => [
            [
                'class' => RotatingFileHandler::class,
                'constructor' => [
                    runtime_path() . '/logs/process_consume_supertour_tickets.log',
                    7,
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
    'process_export_draw_results' => [
        'handlers' => [
            [
                'class' => RotatingFileHandler::class,
                'constructor' => [
                    runtime_path() . '/logs/process_export_draw_results.log',
                    7,
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
