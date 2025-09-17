<?php

namespace app\model;

use support\Model;

class PrizeConfigurationPosition extends Model
{
    protected $table = 'prize_configuration_positions';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'prize_configuration_id',
        'position',
        'prize_type',
        'prize_amount_rub',
        'prize_percentage',
        'prize_description',
        'currency_id'
    ];

    protected $casts = [
        'position' => 'integer',
        'prize_amount_rub' => 'integer',
        'prize_percentage' => 'integer',
        'currency_id' => 'integer'
    ];

    /**
     * Связь с конфигурацией призов
     */
    public function prizeConfiguration()
    {
        return $this->belongsTo(PrizeConfiguration::class, 'prize_configuration_id');
    }

    /**
     * Связь с валютой
     */
    public function currency()
    {
        return $this->belongsTo(PaymentCurrency::class, 'currency_id');
    }

    /**
     * Проверить, является ли приз денежным
     */
    public function isMoneyPrize(): bool
    {
        return $this->prize_type === 'money';
    }

    /**
     * Проверить, является ли приз процентным
     */
    public function isPercentagePrize(): bool
    {
        return $this->prize_type === 'percentage';
    }

    /**
     * Проверить, является ли приз товарным
     */
    public function isProductPrize(): bool
    {
        return $this->prize_type === 'product';
    }

    /**
     * Получить значение приза (сумма или процент)
     */
    public function getPrizeValue(): ?int
    {
        if ($this->isMoneyPrize()) {
            return $this->prize_amount_rub;
        }
        
        if ($this->isPercentagePrize()) {
            return $this->prize_percentage;
        }
        
        return null;
    }
} 