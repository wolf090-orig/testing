<?php

namespace app\validations;

use app\classes\Validator\RequestValidation;

class GetLotteryInfoRequest extends RequestValidation
{
    protected $rule = [
        'lottery_id' => 'require|number|min:1',
    ];
    protected $message;

    public function __construct()
    {
        $this->message = [
            'lottery_id' => trans('validation.lottery_id'),
        ];
    }
}
