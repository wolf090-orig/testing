<?php

namespace app\model;

use support\Model;

class LotteryPrice extends Model
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
    protected $table = 'lottery_prices';
    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';
    protected $fillable = [
        'lottery_type_id',
        'price',
    ];
}