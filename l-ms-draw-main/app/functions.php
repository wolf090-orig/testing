<?php

use app\responses\JsonResponse;
use support\Response;

 /**
 * Returns a success response.
 *
 * @param array $data The data to include in the response.
 * @param int $status The HTTP status code.
 * @param int $options JSON encoding options.
 * @return Response The success response.
 */
function success(array $data, int $status = 200, int $options = JSON_UNESCAPED_UNICODE): Response
{
    return JsonResponse::success($data, $status, $options);
}

/**
 * Returns a warning response.
 *
 * @param array $errors The errors to include in the response.
 * @param int $status The HTTP status code.
 * @param int $options JSON encoding options.
 * @return Response The warning response.
 */
function warning(array $errors, int $status = 400, int $options = JSON_UNESCAPED_UNICODE): Response
{
    return JsonResponse::warning($errors, $status, $options);
}

/**
 * Returns an error response.
 *
 * @param array $errors The errors to include in the response.
 * @param int $status The HTTP status code.
 * @param int $options JSON encoding options.
 * @return Response The error response.
 */
function error(array $errors, int $status = 500, int $options = JSON_UNESCAPED_UNICODE): Response
{
    return JsonResponse::error($errors, $status, $options);
}
