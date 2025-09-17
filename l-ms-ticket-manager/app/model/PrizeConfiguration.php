<?php

namespace app\model;

use support\Model;

class PrizeConfiguration extends Model
{
    protected $table = 'prize_configurations';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'lottery_type_id',
        'name',
        'description',
        'is_active',
        'positions_count',
        'prize_fund_percentage',
        'dynamic_distribution_rules'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'positions_count' => 'integer',
        'prize_fund_percentage' => 'integer',
        'dynamic_distribution_rules' => 'array'
    ];

    /**
     * Связь с типом лотереи
     */
    public function lotteryType()
    {
        return $this->belongsTo(LotteryTypes::class, 'lottery_type_id');
    }

    /**
     * Связь с позициями призов
     */
    public function positions()
    {
        return $this->hasMany(PrizeConfigurationPosition::class, 'prize_configuration_id')->orderBy('position');
    }

    /**
     * Связь с лотереями, использующими эту конфигурацию
     */
    public function lotteries()
    {
        return $this->hasMany(LotteryNumber::class, 'prize_configuration_id');
    }

    /**
     * Получить правила динамического распределения
     */
    public function getDynamicRules(): ?array
    {
        return $this->dynamic_distribution_rules;
    }

    /**
     * Проверить, есть ли динамическое распределение
     */
    public function hasDynamicDistribution(): bool
    {
        return !is_null($this->dynamic_distribution_rules);
    }
} 