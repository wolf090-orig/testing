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
    public $timestamps = false;
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
        "lottery_name",
        "lottery_id",
        "draw_date",
        "end_date",
        "drawn_at"
    ];

    public function winnedTickets()
    {
        return $this->hasMany(Ticket::class, 'lottery_id', 'lottery_id')->where('is_winner', true);
    }
}
