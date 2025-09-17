<?php

namespace app\model;

use support\Model;

/**
 * Модель билетов в корзинах пользователей
 * Временное резервирование билетов перед покупкой
 */
class UserBasketTicket extends Model
{
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_basket_tickets';
    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';
    protected $fillable = [
        'basket_id',
        'ticket_id',
        'lottery_id',
    ];

    /**
     * Связь с лотереей
     */
    public function lottery()
    {
        $relation = $this->belongsTo(LotteryNumber::class, 'lottery_id', 'id')
            ->with('price');
        return $relation;
    }

    public function getBasketData()
    {
        return [
            "ticket_id" => $this->ticket_id,
            "lottery_id" => $this->lottery_id,
        ];
    }

    public function getPrice(): float
    {
        return $this->lottery->getPrice();
    }

    public function getLottery(): LotteryNumber
    {
        return $this->lottery;
    }

    public function getTicketIdAttribute($value)
    {
        // Форматирует номер билета с ведущими нулями до 7 знаков для возврата на фронт
        return sprintf('%07d', $value);
    }
}
