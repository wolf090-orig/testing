<?php

namespace app\model;

use support\Model;

class CancelReasons extends Model
{
    const EXPIRED = 'expired';
    const CANCELED_BY_USER = 'canceled_by_user';
    const PAYMENT_FAILED = 'payment_failed';
    const PAYMENT_SUCCESS = 'payment_success';
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'cancel_reasons';

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
    public $timestamps = false;

    protected $fillable = [
        'name',
        'description'
    ];
}
