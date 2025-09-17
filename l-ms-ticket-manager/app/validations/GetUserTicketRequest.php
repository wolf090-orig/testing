<?php

namespace app\validations;

use app\classes\Validator\RequestValidation;

class GetUserTicketRequest extends RequestValidation
{
    protected $rule = [
        'with_leaderboard' => 'boolean',
    ];
    protected $message;

    public function __construct()
    {
        $this->message = [
            'with_leaderboard' => trans('validation.with_leaderboard'),
        ];
    }
}
