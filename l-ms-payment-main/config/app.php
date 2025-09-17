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

use support\Request;

return [
    'debug' => getenv('SERVER_DEBUG', true),
    'error_reporting' => E_ALL,
    'default_timezone' => getenv('APP_TIMEZONE') ?? 'Europe/Moscow',
    'request_class' => Request::class,
    'public_path' => base_path() . DIRECTORY_SEPARATOR . 'public',
    'runtime_path' => base_path(false) . DIRECTORY_SEPARATOR . 'runtime',
    'controller_suffix' => 'Controller',
    'controller_reuse' => false,
    
    // Настройки для Telegram-авторизации
    'env' => getenv('APP_ENV', 'production'),
    'telegram_bot_token' => getenv('TELEGRAM_BOT_TOKEN', ''),
    'telegram_auth_max_age' => (int)getenv('TELEGRAM_AUTH_MAX_AGE', 3600), // Максимальное время жизни данных авторизации Telegram в секундах (по умолчанию 1 час)
    
    // Настройки режима разработки для Telegram
    'dev_user_override_enabled' => getenv('DEV_USER_OVERRIDE_ENABLED', false),
    'dev_secret_key' => getenv('DEV_SECRET_KEY', ''),
    
    // Настройки для middleware
    'rate_limit_attempts' => (int)getenv('RATE_LIMIT_ATTEMPTS', 10),
    'rate_limit_seconds' => (int)getenv('RATE_LIMIT_SECONDS', 60),
    'request_logging' => (bool)getenv('REQUEST_LOGGING', true),
];
