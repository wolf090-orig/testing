<?php

namespace app\responses;

use support\Response;

class JsonResponse
{
    /**
     * Returns a success response.
     *
     * @param array $data The data to include in the response.
     * @param int $status The HTTP status code.
     * @param int $options JSON encoding options.
     * @return Response The success response.
     */
    public static function success(array $data, int $status = 200, int $options = JSON_UNESCAPED_UNICODE): Response
    {
        $result = [
            "status" => "success",
            "data" => $data,
            "errors" => null,
        ];
        return new Response($status, ['Content-Type' => 'application/json'], json_encode($result, $options));
    }

    /**
     * Returns a warning response.
     *
     * @param array $errors The errors to include in the response.
     * @param int $status The HTTP status code.
     * @param int $options JSON encoding options.
     * @return Response The warning response.
     */
    public static function warning(array $errors, int $status = 400, int $options = JSON_UNESCAPED_UNICODE): Response
    {
        $result = [
            "status" => "warning",
            "data" => null,
            "errors" => $errors,
        ];
        return new Response($status, ['Content-Type' => 'application/json'], json_encode($result, $options));
    }

    /**
     * Returns an error response.
     *
     * @param array $errors The errors to include in the response.
     * @param int $status The HTTP status code.
     * @param int $options JSON encoding options.
     * @return Response The error response.
     */
    public static function error(array $errors, int $status = 500, int $options = JSON_UNESCAPED_UNICODE): Response
    {
        $result = [
            "status" => "error",
            "data" => null,
            "errors" => $errors,
        ];
        return new Response($status, ['Content-Type' => 'application/json'], json_encode($result, $options));
    }
}
