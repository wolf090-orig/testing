<?php

namespace app\repository;

use app\classes\Interfaces\RateLimitStorageInterface;
use support\Redis;

class RateLimitStorageRepository implements RateLimitStorageInterface
{
    /**
     * Увеличивает значение счетчика по ключу
     *
     * @param string $key Ключ для хранения
     * @return int Новое значение счетчика
     */
    public function incr(string $key): int
    {
        return Redis::incr($key);
    }

    /**
     * Устанавливает время жизни ключа
     *
     * @param string $key Ключ
     * @param int $seconds Время жизни в секундах
     * @return void
     */
    public function expire(string $key, int $seconds): void
    {
        Redis::expire($key, $seconds);
    }

    /**
     * Получает оставшееся время жизни ключа (TTL)
     *
     * @param string $key Ключ
     * @return int Оставшееся время в секундах или -1, если ключ не существует или не имеет TTL
     */
    public function ttl(string $key): int
    {
        return Redis::ttl($key);
    }
}
