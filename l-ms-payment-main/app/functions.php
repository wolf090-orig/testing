<?php

use app\responses\JsonResponse;
use support\Response;

/**
 * Returns a success response.
 *
 * @param array $data The data to include in the response.
 * @param string $message The message to include in the response.
 * @param int $status The HTTP status code.
 * @param array $headers The HTTP headers.
 * @return Response The success response.
 */
function success(array $data = [], string $message = "Успешно", int $status = 200, array $headers = []): Response
{
    return JsonResponse::success($data, $message, $status, $headers);
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
function warning(array $errors, string $message = "Предупреждение", int $status = 400, array $headers = []): Response
{
    return JsonResponse::warning($errors, $message, $status, $headers);
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
function error(array $errors, string $message = "Ошибка сервера", int $status = 500, array $headers = []): Response
{
    return JsonResponse::error($errors, $message, $status, $headers);
}
