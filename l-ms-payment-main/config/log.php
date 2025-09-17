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
use Monolog\Processor\MemoryUsageProcessor;
use Monolog\Processor\ProcessIdProcessor;

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
                'class' => RotatingFileHandler::class,
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

    // Канал для создания платежей (вся последовательность действий)
    'payment_create' => [
        'handlers' => [
            [
                'class' => RotatingFileHandler::class,
                'constructor' => [
                    runtime_path() . '/logs/payment_create.log',
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

    // Канал для операций с партициями БД
    'payment_partition' => [
        'handlers' => [
            [
                'class' => RotatingFileHandler::class,
                'constructor' => [
                    runtime_path() . '/logs/payment_partition.log',
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

    // Канал для логирования HTTP запросов и ответов
    'request' => [
        'handlers' => [
            [
                'class' => RotatingFileHandler::class,
                'constructor' => [
                    runtime_path() . '/logs/request.log',
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

    // Канал для логирования callback'ов от платежных шлюзов
    'payment_callback' => [
        'handlers' => [
            [
                'class' => RotatingFileHandler::class,
                'constructor' => [
                    runtime_path() . '/logs/payment_callback.log',
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
    
    // Канал для логирования сервиса обработки callback'ов
    'payment_callbacks' => [
        'handlers' => [
            [
                'class' => RotatingFileHandler::class,
                'constructor' => [
                    runtime_path() . '/logs/payment_callback.log',
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
    
    // Канал для логирования команды обработки callback'ов
    'command_process_callbacks' => [
        'handlers' => [
            [
                'class' => RotatingFileHandler::class,
                'constructor' => [
                    runtime_path() . '/logs/command_process_callbacks.log',
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
    
    // Канал для логирования команды проверки статусов FPGate
    'command_check_fpgate_status' => [
        'handlers' => [
            [
                'class' => RotatingFileHandler::class,
                'constructor' => [
                    runtime_path() . '/logs/command_check_fpgate_status.log',
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
    
    // Канал для логирования процесса обработки callback'ов
    'process_payment_callbacks' => [
        'handlers' => [
            [
                'class' => RotatingFileHandler::class,
                'constructor' => [
                    runtime_path() . '/logs/process_payment_callbacks.log',
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
    
    // Канал для логирования процесса проверки статусов FPGate
    'process_fpgate_status_check' => [
        'handlers' => [
            [
                'class' => RotatingFileHandler::class,
                'constructor' => [
                    runtime_path() . '/logs/process_fpgate_status_check.log',
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
    
    // Канал для логирования сервиса обработки callback'ов
    'payment_callbacks' => [
        'handlers' => [
            [
                'class' => RotatingFileHandler::class,
                'constructor' => [
                    runtime_path() . '/logs/payment_callbacks.log',
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
    
    // Канал для логирования сервиса проверки статусов платежей
    'payment_status_check' => [
        'handlers' => [
            [
                'class' => RotatingFileHandler::class,
                'constructor' => [
                    runtime_path() . '/logs/payment_status_check.log',
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
