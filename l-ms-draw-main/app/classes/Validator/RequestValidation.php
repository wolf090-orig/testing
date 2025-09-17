<?php

namespace app\classes\Validator;

use think\Validate;

class RequestValidation extends Validate
{
    public function ruleKeys()
    {
        return array_keys($this->rule);
    }

    public static function validated(array $data): array
    {
        $validation = new (get_called_class())();
        $validation->failException(true); // throws exception
        $validation->check($data);

        $response = [];
        foreach ($validation->ruleKeys() as $key) {
            $response[$key] = $data[$key];
        }

        return $response;
    }
}
