<?php

namespace app\validations;

use app\classes\Validator\RequestValidation;

class GetUserTicketsRequest extends RequestValidation
{
    protected $rule = [
        'lottery_id' => 'number|min:1',
        'status' => 'alpha|in:history,active,winner',
    ];
    protected $message;

    public function __construct()
    {
        $this->message = [
            'lottery_id' => trans('validation.lottery_id'),
            'status' => trans('validation.ticket_status'),
        ];
    }
}
