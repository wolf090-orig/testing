<?php

namespace app\classes\Responses;

use app\enums\ResponseTypeEnum;
use support\Response;

class ApiResponse extends Response
{
    public function __construct(
        $data,
        $message = "Data found",
        int $code = 200,
        array $headers = [],
        $type = ResponseTypeEnum::SUCCESS,
        array $errors = []
    ) {

        $response = [
            "message" => $message,
            "status" => $type,
            "data" => $data,
            "errors" => $errors
        ];

        // $debug = debug();
        // if ($debug && $debug->isEnable()) {
        //     $response['debug'] = $debug->getLogs();
        // }

        parent::__construct($code, array_replace(['Content-Type' => 'application/json'], $headers), json_encode($response, JSON_UNESCAPED_UNICODE));
    }
}
