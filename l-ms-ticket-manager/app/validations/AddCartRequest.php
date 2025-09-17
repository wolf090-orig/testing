<?php

namespace app\validations;

use app\classes\Validator\RequestValidation;

class AddCartRequest extends RequestValidation
{
    protected $rule = [
        'lottery_id' => 'require|number|min:1',
        'ticket_numbers' => 'numbersValidation',
        'quantity' => 'number|min:1|max:20',
    ];
    protected $message;

    public function __construct()
    {
        $this->message = [
            'lottery_id' => trans('validation.lottery_id'),
            'ticket_numbers' => trans('validation.ticket_number_id'),
            'quantity' => trans('validation.quantity'),
        ];
    }

    protected function numbersValidation($value, $rule, $data = [])
    {
        // Если ticket_numbers не передан, но указан quantity, это допустимо
        if (empty($value) && isset($data['quantity']) && $data['quantity'] > 0) {
            return true;
        }
        
        // Если значение не массив, но не пустое - ошибка
        if (!is_array($value) && !empty($value)) {
            return "ticket_numbers should be an array";
        }
        
        // Если это массив и он не пустой, проверяем его размер
        if (is_array($value) && count($value) > 20) {
            return "maximum number of tickets is 20";
        }
        
        return true;
    }
}
