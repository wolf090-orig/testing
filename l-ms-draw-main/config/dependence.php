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


use app\classes\Interfaces\LotteryRepositoryInterface;
use app\classes\Interfaces\MsTicketManagerInterface;
use app\classes\Interfaces\RandomizerClientInterface;
use app\classes\Interfaces\TicketRepositoryInterface;
use app\clients\MsTicketManagerClient;
use app\clients\MsTicketManagerFake;
use app\clients\RandomClient;
use app\repository\LotteryRepositoryDB;
use app\repository\TicketRepositoryDB;
use app\repository\TicketRepositoryFake;

$fakeDependencies = [
    TicketRepositoryInterface::class => TicketRepositoryFake::class,
    MsTicketManagerInterface::class => MsTicketManagerFake::class,
    RandomizerClientInterface::class => RandomClient::class,
    LotteryRepositoryInterface::class => LotteryRepositoryDB::class,
];

/**
 * @var array $realDependencies Массив реальных зависимостей для рабочего режима
 */
$realDependencies = [
    TicketRepositoryInterface::class => TicketRepositoryDB::class,
    MsTicketManagerInterface::class => MsTicketManagerClient::class,
    RandomizerClientInterface::class => RandomClient::class,
    LotteryRepositoryInterface::class => LotteryRepositoryDB::class,
];

$dependencies = getenv('APP_ENV') == "test" ? $fakeDependencies : $realDependencies;

foreach ($dependencies as $interface => $option) {
    $class = is_array($option) ? $option['class'] : $option;
    $dependencies[$interface] = DI\create($class);
}

return $dependencies;
