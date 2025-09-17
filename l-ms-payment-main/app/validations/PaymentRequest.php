<?php

namespace app\validations;

use app\classes\Validator\RequestValidation;

class PaymentRequest extends RequestValidation
{
    protected $rule = [
        'internal_order_id' => 'require|max:100',
        'user_id' => 'require|number|min:1',
        'amount' => 'require|number|min:1',
        'currency' => 'require|in:RUB,USD,EUR',
        'payment_method' => 'require|paymentMethodValidation'
    ];
    
    protected $message;

    public function __construct()
    {
        $this->message = [
            'internal_order_id' => [
                'require' => 'internal_order_id обязателен',
                'max' => 'internal_order_id не может быть длиннее 100 символов'
            ],
            'user_id' => [
                'require' => 'user_id обязателен',
                'number' => 'user_id должен быть числом',
                'min' => 'user_id должен быть больше 0'
            ],
            'amount' => [
                'require' => 'amount обязателен',
                'number' => 'amount должен быть числом',
                'min' => 'amount должен быть больше 0'
            ],
            'currency' => [
                'require' => 'currency обязательна',
                'in' => 'currency должна быть RUB, USD или EUR'
            ],
            'payment_method' => [
                'require' => 'payment_method обязателен',
                'paymentMethodValidation' => 'payment_method должен быть card или sbp'
            ]
        ];
    }

    protected function paymentMethodValidation($value, $rule, $data = [])
    {
        if (!in_array($value, ['card', 'sbp'])) {
            return 'payment_method должен быть card или sbp';
        }
        
        return true;
    }
}
