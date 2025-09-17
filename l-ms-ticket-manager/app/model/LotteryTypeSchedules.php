<?php

namespace app\model;

use support\Model;

class LotteryTypeSchedules extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'lottery_types_schedules';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    protected $fillable = [
        'type_id',
        'sale_start_time',
        'sale_end_time',
        'sale_draw_time',
    ];
}
