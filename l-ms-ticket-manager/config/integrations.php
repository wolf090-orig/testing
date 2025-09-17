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

return [
    'ms_profile' => [
        'base_uri' => getenv("MS_PROFILE_BASE_URI"),
        'token' => getenv("MS_PROFILE_TOKEN")
    ],
    'ms_payment' => [
        'base_uri' => getenv("MS_PAYMENT_BASE_URI") ?: 'http://ms-payment:8080',
        'token' => getenv("MS_PAYMENT_TOKEN") ?: 'MS_TICKET_MANAGER_TOKEN'
    ]
];
