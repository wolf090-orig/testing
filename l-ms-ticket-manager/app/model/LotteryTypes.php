<?php

namespace app\model;

use support\Model;

class LotteryTypes extends Model
{
    // Константы соответствуют типам лотерей из сидера
    const DAILY_FIXED_TYPE = 'daily_fixed';
    const DAILY_DYNAMIC_TYPE = 'daily_dynamic';
    const JACKPOT_TYPE = 'jackpot';
    const SUPERTOUR_TYPE = 'supertour';
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'lottery_types';

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
        'type',
        'description'
    ];
}
