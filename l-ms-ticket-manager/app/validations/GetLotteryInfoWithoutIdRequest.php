<?php

namespace app\validations;

use app\classes\Validator\RequestValidation;
use app\enums\LotteryTypeEnum;

class GetLotteryInfoWithoutIdRequest extends RequestValidation
{
    protected $rule = [
        'lottery_type' => 'typeValidation',
        'status' => 'statusValidation',
        'country_code' => 'countryCodeValidation',
    ];

    protected $message;

    public function __construct()
    {
        $this->message = [
            'lottery_type' => trans('validation.lottery_type'),
            'status' => trans('validation.status'),
            'country_code' => trans('validation.country_code'),
        ];
    }

    protected function typeValidation($value): true|string
    {
        return LotteryTypeEnum::isValidType($value)
            ? true
            : 'type must be one of: ' . implode(', ', LotteryTypeEnum::getAllTypes());
    }

    protected function statusValidation($value): true|string
    {
        return in_array($value, ['history', 'active'])
            ? true
            : 'type must be one of: history, active';
    }

    protected function countryCodeValidation($value): true|string
    {
        // Опциональное поле - если пусто, то валидно
        if (empty($value)) {
            return true;
        }

        // Проверяем что это строка
        if (!is_string($value)) {
            return 'country_code must be a string';
        }

        // Проверяем длину кода страны (обычно 2 символа)
        $countryCode = trim(strtoupper($value));
        if (strlen($countryCode) !== 2) {
            return 'country_code must be exactly 2 characters';
        }

        // Проверяем что содержит только буквы
        if (!preg_match('/^[A-Z]{2}$/', $countryCode)) {
            return 'country_code must contain only letters';
        }

        return true;
    }
}
