<?php

namespace app\model;

use support\Model;

class Country extends Model
{
    /**
     * Имя таблицы
     */
    protected $table = 'countries';

    /**
     * Поля, доступные для массового заполнения
     */
    protected $fillable = [
        'code',
        'name',
        'sort_order',
        'active'
    ];

    /**
     * Поля с датами
     */
    protected $dates = [
        'created_at',
        'updated_at'
    ];

    /**
     * Получить активные страны в порядке сортировки
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getActive()
    {
        return static::where('active', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Получить коды активных стран
     *
     * @return array
     */
    public static function getActiveCodes(): array
    {
        return static::where('active', true)
            ->orderBy('sort_order')
            ->pluck('code')
            ->toArray();
    }

    /**
     * Получить ID активных стран
     *
     * @return array
     */
    public static function getActiveIds(): array
    {
        return static::where('active', true)
            ->orderBy('sort_order')
            ->pluck('id')
            ->toArray();
    }

    /**
     * Найти страну по коду
     *
     * @param string $code
     * @return Country|null
     */
    public static function findByCode(string $code): ?Country
    {
        return static::where('code', $code)->first();
    }

    /**
     * Получить лотереи этой страны
     */
    public function lotteries()
    {
        return $this->hasMany(LotteryNumber::class, 'country_id', 'id');
    }
}
