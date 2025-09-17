<?php

namespace app\model;

use support\Model;
use app\model\UserTicketPurchase;

class WinnerTicket extends Model
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
    protected $table = 'winner_tickets';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    protected $fillable = [
        'user_ticket_purchase_id',
        'lottery_id',
        'user_id',
        'winner_position',
        'payout_amount',
        'payout_currency_id',
        'is_paid',
        'paid_at'
    ];

    protected $casts = [
        'winner_position' => 'integer',
        'payout_amount' => 'integer',
        'is_paid' => 'boolean',
        'paid_at' => 'datetime'
    ];

    /**
     * Get the user ticket purchase associated with the winner ticket.
     */
    public function userTicketPurchase()
    {
        return $this->belongsTo(UserTicketPurchase::class, 'user_ticket_purchase_id', 'id');
    }
}
