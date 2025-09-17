<?php

namespace app\model;

use app\classes\Interfaces\MsProfileInterface;

class MsProfileFake implements MsProfileInterface
{
    private int $id;
    private bool $isIdentified;

    public function __construct()
    {
        $this->id = 123456789; // Фиксированный ID для тестирования
        $this->isIdentified = true; // Всегда считаем пользователя идентифицированным
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): int
    {
        $this->id = $id;
        return $this->id;
    }

    public function getByUserId(int $userId): MsProfileInterface
    {
        // Просто устанавливаем userId и возвращаем себя
        $this->id = $userId;
        return $this;
    }

    public function isIdentified(): bool
    {
        return $this->isIdentified;
    }

    /**
     * Дополнительный метод для совместимости
     * 
     * @param int $userId
     * @return MsProfileInterface
     */
    public function find(int $userId): MsProfileInterface
    {
        return $this->getByUserId($userId);
    }

    /**
     * Дополнительный метод для совместимости
     * 
     * @param string $phone
     * @return MsProfileInterface
     */
    public function init(string $phone): MsProfileInterface
    {
        // Просто возвращаем себя без изменений
        return $this;
    }
} 