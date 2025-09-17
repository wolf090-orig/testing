<?php

namespace app\validations;

use app\classes\Validator\RequestValidation;

class GetTicketsRequest extends RequestValidation
{
    protected $rule = [
        'type' => 'require|typeValidation',
        'mask' => 'maskValidation',
        'quantity' => 'number|min:1|max:100',
        'page' => 'number|min:1',
        'page_size' => 'number|min:1|max:10',
        'lottery_id' => 'require|number|min:1',
    ];
    protected $message;

    public function __construct()
    {
        $this->message = [
            'type' => trans('validation.type'),
            'mask' => trans('validation.mask'),
            'quantity' => trans('validation.quantity'),
            'page' => trans('validation.page'),
            'page_size' => trans('validation.page_size'),
            'lottery_id' => trans('validation.lottery_id'),
        ];
    }

    protected function typeValidation($value, $rule, $data = [])
    {
        if (in_array($value, ['auto', 'manual'])) {
            return true;
        }
        return "type can be auto or manual";
    }

    protected function maskValidation($value, $rule, $data = [])
    {
        if (empty($value) && !empty($data['quantity'])) {
            return true;
        }
        
        if (preg_match('/^[0-9_]{7}$/', $value)) {
            return true;
        }
        return "mask can only contain numbers 0-9 and _, with maximum length 7 characters";
    }
}
