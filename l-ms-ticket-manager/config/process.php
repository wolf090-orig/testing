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

global $argv;

return [
    // File update detection and automatic reload
    'monitor' => [
        'handler' => process\Monitor::class,
        'reloadable' => false,
        'constructor' => [
            // Monitor these directories
            'monitorDir' => array_merge([
                app_path(),
                config_path(),
                base_path() . '/process',
                base_path() . '/support',
                base_path() . '/resource',
                base_path() . '/.env',
                base_path() . '/routes',
            ], glob(base_path() . '/plugin/*/app'), glob(base_path() . '/plugin/*/config'), glob(base_path() . '/plugin/*/api')),
            // Files with these suffixes will be monitored
            'monitorExtensions' => [
                'php',
                'html',
                'htm',
                'env'
            ],
            'options' => [
                'enable_file_monitor' => !in_array('-d', $argv) && DIRECTORY_SEPARATOR === '/',
                'enable_memory_monitor' => DIRECTORY_SEPARATOR === '/',
            ]
        ]
    ],
    // Автоматическое закрытие истекших корзин каждую минуту
    'close_expired_baskets' => [
        'handler' => \process\CloseBasketsOnTimeProcess::class
    ],
    // Автогенерация лотерей ежедневно в 00:00
    'generate_lottery_numbers' => [
        'handler' => \process\GenerateLotteryNumbersProcess::class
    ],
    // Автогенерация билетов каждую минуту для пополнения пула
    'auto_generate_tickets' => [
        'handler' => \process\AutoGenerateTicketsProcess::class
    ],
    // Автоматический экспорт активных лотерей каждые 5 минут
    'export_active_lotteries' => [
        'handler' => \process\ExportActiveLotteriesProcess::class
    ],
    // Автоматический экспорт билетов всех типов каждые 2 минуты
    'export_tickets' => [
        'handler' => \process\ExportTicketsProcess::class
    ],
    // Автоматический расчет количества победителей каждую минуту
    'calculate_lottery_winners' => [
        'handler' => \process\CalculateLotteryWinnersProcess::class
    ],
    // Автоматический экспорт конфигурации победителей каждую минуту
    'export_lottery_winners_config' => [
        'handler' => \process\ExportLotteryWinnersConfigProcess::class
    ],
    // Автоматический импорт выигрышных билетов из Kafka (непрерывно)
    'import_winner_tickets' => [
        'handler' => \process\ImportWinnerTicketsProcess::class
    ],
    //    'create_lottery' => [
    //        'handler' => \process\CreateLotteryTickets::class
    //    ]
];
