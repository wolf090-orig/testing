<?php

namespace app\classes\Interfaces;

use app\dto\UserDto;

/**
 * Интерфейс для работы с пользователями
 */
interface UserRepositoryInterface
{
    /**
     * Получает профиль пользователя
     *
     * @param int $telegramId
     * @return UserDto|null
     */
    public function getUserProfile(int $telegramId): ?UserDto;

    /**
     * Обновляет настройки пользователя
     *
     * @param int $telegramId
     * @param array $settings
     * @return bool
     */
    public function updateUserSettings(int $telegramId, array $settings): bool;

    /**
     * Получает список активных стран и языков
     *
     * @return array Массив с ключами 'countries' и 'languages'
     */
    public function getSettingsList(): array;

    /**
     * Находит или создает пользователя из данных Telegram
     *
     * @param array $telegramData
     * @return UserDto
     */
    public function findOrCreateFromTelegram(array $telegramData): UserDto;
} 