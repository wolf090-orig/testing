<?php

namespace app\classes\Interfaces;

interface MsProfileInterface
{
    /**
     * Получает идентификатор профиля.
     *
     * @return int
     */
    public function getId(): int;

    /**
     * Устанавливает идентификатор профиля.
     *
     * @param int $id Идентификатор профиля
     * @return int Возвращает установленный идентификатор
     */
    public function setId(int $id): int;

    /**
     * Получает удаленный профиль пользователя по его идентификатору.
     *
     * @param int $userId Идентификатор пользователя
     * @return MsProfileInterface Возвращает интерфейс профиля пользователя
     */
    public function getByUserId(int $userId): MsProfileInterface;

    /**
     * Проверяет, идентифицирован ли профиль.
     *
     * @return bool
     */
    public function isIdentified(): bool;
}
