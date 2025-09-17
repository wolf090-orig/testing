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

/**
 * @var array $fakeDependencies Массив зависимостей для отладочного режима
 */

use app\classes\Interfaces\BasketRepositoryInterface;
use app\classes\Interfaces\MsProfileInterface;
use app\classes\Interfaces\MsPaymentInterface;
use app\classes\Interfaces\TicketRepositoryInterface;
use app\clients\MsProfile;
use app\clients\MsPayment;
use app\clients\MsPaymentFake;
use app\repository\ticket\TicketRepositoryDB;
use app\repository\ticket\TicketRepositoryFake;

$fakeDependencies = [
    TicketRepositoryInterface::class => TicketRepositoryFake::class,
    BasketRepositoryInterface::class => \app\repository\basket\BasketRepositoryFake::class,
    MsProfileInterface::class => \app\model\MsProfileFake::class,
    MsPaymentInterface::class => MsPaymentFake::class,
];

/**
 * @var array $realDependencies Массив реальных зависимостей для рабочего режима
 */
$realDependencies = [
    TicketRepositoryInterface::class => TicketRepositoryDB::class,
    BasketRepositoryInterface::class => \app\repository\basket\BasketRepositoryDB::class,
    MsProfileInterface::class => \app\model\MsProfileFake::class,
    MsPaymentInterface::class => MsPayment::class,
];

$dependencies = getenv('APP_ENV') == "test" ? $fakeDependencies : $realDependencies;

foreach ($dependencies as $interface => $option) {
    $class = is_array($option) ? $option['class'] : $option;
    $dependencies[$interface] = DI\create($class);
}

return $dependencies;
