<?php

namespace app\model;

use support\Model;

class PaymentStatus extends Model
{
    const PAID = 'paid';
    const PENDING = 'pending';
    const FAILED = 'failed';
    const REFUNDED = 'failed';

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
    protected $table = 'payment_statuses';
    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';
    protected $fillable = [
        'name',
        'description'
    ];
}
