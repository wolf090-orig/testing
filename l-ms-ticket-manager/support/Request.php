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

namespace support;

use app\classes\Interfaces\MsProfileInterface;
use Exception;

/**
 * Class Request
 * @package support
 */
class Request extends \Webman\Http\Request
{
    private ?MsProfileInterface $user;
    public $telegramId;

    public function __construct($buffer)
    {
        parent::__construct($buffer);
        $this->user = null;
        $this->telegramId = null;
    }

    /**
     * Получает пользователя, инициализируя его с telegramId, 
     * который был установлен через TelegramAuthMiddleware
     * 
     * @throws Exception
     */
    public function user(): ?MsProfileInterface
    {
        // Если пользователь уже загружен, возвращаем его
        if ($this->user !== null) {
            return $this->user;
        }
        
        // Проверяем, установлен ли telegramId через middleware
        if (!empty($this->telegramId)) {
            $this->user = Container::make(MsProfileInterface::class, []);
            $this->user->setId($this->telegramId);
            return $this->user;
        }
        
        return null;
    }

    /**
     * Получает авторизованного пользователя или выбрасывает исключение
     * 
     * @throws Exception
     */
    public function getUser(): MsProfileInterface
    {
        if (empty($this->user)) {
            // Пытаемся инициализировать пользователя
            $user = $this->user();
            
            if (empty($user)) {
                throw new Exception("Could not find user!", 401);
            }
        }
        
        return $this->user;
    }
}