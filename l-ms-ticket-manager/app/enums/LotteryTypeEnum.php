<?php

namespace app\enums;

/**
 * Типы лотерей в новой архитектуре
 */
class LotteryTypeEnum
{
    public const DAILY_FIXED = 'daily_fixed';
    public const DAILY_DYNAMIC = 'daily_dynamic';
    public const JACKPOT = 'jackpot';
    public const SUPERTOUR = 'supertour';

    /**
     * Получить все доступные типы лотерей
     * 
     * @return array
     */
    public static function getAllTypes(): array
    {
        return [
            self::DAILY_FIXED,
            self::DAILY_DYNAMIC,
            self::JACKPOT,
            self::SUPERTOUR,
        ];
    }

    /**
     * Проверить, является ли тип валидным
     * 
     * @param string $type
     * @return bool
     */
    public static function isValidType(string $type): bool
    {
        return in_array($type, self::getAllTypes());
    }

    /**
     * Получить описания типов лотерей
     * 
     * @return array
     */
    public static function getTypeDescriptions(): array
    {
        return [
            self::DAILY_FIXED => 'Ежедневная лотерея с фиксированным призом',
            self::DAILY_DYNAMIC => 'Ежедневная лотерея с динамическим призом',
            self::JACKPOT => 'Джекпот лотерея с накопительным призовым фондом',
            self::SUPERTOUR => 'Супертур с крупными призами',
        ];
    }
}
