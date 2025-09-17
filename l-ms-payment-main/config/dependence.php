<?php

use app\classes\Interfaces\RateLimitStorageInterface;
use app\interfaces\PaymentGatewayInterface;
use app\interfaces\PaymentTransactionRepositoryInterface;
use app\interfaces\PaymentCallbackRepositoryInterface;
use app\interfaces\PaymentGatewayResponseRepositoryInterface;
use app\repository\RateLimitStorageRepository;
use app\repository\PaymentTransactionRepository;
use app\repository\PaymentCallbackRepository;
use app\repository\PaymentGatewayResponseRepository;
use app\clients\FPGateApiClient;
use app\clients\FakePaymentGateway;

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

/**
 * @var array $fakeDependencies Массив зависимостей для тестового режима (APP_ENV=test)
 */
$fakeDependencies = [
    RateLimitStorageInterface::class => RateLimitStorageRepository::class,
    PaymentGatewayInterface::class => FakePaymentGateway::class,
    PaymentTransactionRepositoryInterface::class => PaymentTransactionRepository::class,
    PaymentCallbackRepositoryInterface::class => PaymentCallbackRepository::class,
    PaymentGatewayResponseRepositoryInterface::class => PaymentGatewayResponseRepository::class,
];

/**
 * @var array $realDependencies Массив реальных зависимостей для рабочего режима (development/production)
 */
$realDependencies = [
    RateLimitStorageInterface::class => RateLimitStorageRepository::class,
    // PaymentGatewayInterface::class => FakePaymentGateway::class,  // Для разработки используем фейк
    PaymentGatewayInterface::class => FPGateApiClient::class,  // Реальный API (для продакшна)
    PaymentTransactionRepositoryInterface::class => PaymentTransactionRepository::class,
    PaymentCallbackRepositoryInterface::class => PaymentCallbackRepository::class,
    PaymentGatewayResponseRepositoryInterface::class => PaymentGatewayResponseRepository::class,
];

$dependencies = getenv('APP_ENV') == "test" ? $fakeDependencies : $realDependencies;

foreach ($dependencies as $interface => $option) {
    $class = is_array($option) ? $option['class'] : $option;
    $dependencies[$interface] = DI\create($class);
}

return $dependencies;
