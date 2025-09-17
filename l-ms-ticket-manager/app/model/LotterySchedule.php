<?php

namespace app\model;

use support\Model;

class LotterySchedule extends Model
{
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;
    public $typeMap = [];
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'lottery_schedules';
    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';
    protected $fillable = [
        'lottery_id',
        'sale_start_date',
        'sale_end_date',
        'draw_date',
    ];

    public function lotteryNumber()
    {
        return $this->belongsTo(LotteryNumber::class, 'lottery_id');
    }

    public function getDTO(): array
    {
        return [
            'id' => $this->id,
            'sale_start_date' => $this->sale_start_date,
            'sale_end_date' => $this->sale_end_date,
            'draw_date' => $this->draw_date,
            'lottery_name' => $this->lotteryNumber->lottery_name,
        ];
    }
}
