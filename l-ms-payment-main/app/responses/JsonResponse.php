<?php

namespace app\responses;

use app\enums\ResponseTypeEnum;
use support\Response;

class JsonResponse
{
    /**
     * Returns a success response.
     *
     * @param array $data The data to include in the response.
     * @param string $message The message to include in the response.
     * @param int $status The HTTP status code.
     * @param array $headers The HTTP headers.
     * @return Response The success response.
     */
    public static function success(array $data = [], string $message = "Успешно", int $status = 200, array $headers = []): Response
    {
        $result = [
            "message" => $message,
            "status" => ResponseTypeEnum::SUCCESS,
            "data" => $data,
            "errors" => [],
        ];
        
        $headers = empty($headers) ? ['Content-Type' => 'application/json; charset=utf-8'] : $headers;
        
        return new Response(
            $status, 
            $headers, 
            json_encode($result, JSON_UNESCAPED_UNICODE)
        );
    }

    /**
     * Returns a warning response.
     *
     * @param array $errors The errors to include in the response.
     * @param string $message The message to include in the response.
     * @param int $status The HTTP status code.
     * @param array $headers The HTTP headers.
     * @return Response The warning response.
     */
    public static function warning(array $errors, string $message = "Предупреждение", int $status = 400, array $headers = []): Response
    {
        $result = [
            "message" => $message,
            "status" => ResponseTypeEnum::WARNING,
            "data" => [],
            "errors" => $errors,
        ];
        
        $headers = empty($headers) ? ['Content-Type' => 'application/json; charset=utf-8'] : $headers;
        
        return new Response(
            $status, 
            $headers, 
            json_encode($result, JSON_UNESCAPED_UNICODE)
        );
    }

    /**
     * Returns an error response.
     *
     * @param array $errors The errors to include in the response.
     * @param string $message The message to include in the response.
     * @param int $status The HTTP status code.
     * @param array $headers The HTTP headers.
     * @return Response The error response.
     */
    public static function error(array $errors, string $message = "Ошибка сервера", int $status = 500, array $headers = []): Response
    {
        $result = [
            "message" => $message,
            "status" => ResponseTypeEnum::ERROR,
            "data" => [],
            "errors" => $errors,
        ];
        
        $headers = empty($headers) ? ['Content-Type' => 'application/json; charset=utf-8'] : $headers;
        
        return new Response(
            $status, 
            $headers, 
            json_encode($result, JSON_UNESCAPED_UNICODE)
        );
    }
}
