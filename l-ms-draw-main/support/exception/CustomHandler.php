<?php

namespace support\exception;

use app\classes\Responses\ApiResponse;
use app\enums\ResponseTypeEnum;
// use support\bootstrap\Sentry;
use support\Log;
use think\exception\ValidateException;
use Throwable;
use Webman\Exception\ExceptionHandler;
use Webman\Http\Request;
use Webman\Http\Response;

class CustomHandler extends ExceptionHandler
{
    public $dontReport = [
        BusinessException::class,
    ];

    public function report(Throwable $exception): void
    {
        if ($this->shouldntReport($exception)) {
            return;
        }

        $logs = '';
        if ($request = \request()) {
            $logs = $request->getRealIp() . ' ' . $request->method() . ' ' . trim($request->fullUrl(), '/');
        }

//        Log::channel('stdout')->error($logs . PHP_EOL . $exception);

        // Sentry::captureException($exception);
    }

    public function render(Request $request, Throwable $exception): Response
    {
        $errors = [];
        // Для API всегда возвращаем JSON, независимо от заголовков
        if ($exception instanceof ValidateException) {
            $code = 422;
            $errors[] = $exception->getError();
        } else {
            $code = 500;

            $errors[] = [
                'code' => $exception->getCode(),
                'message' => $exception->getMessage()
            ];
        }

        $type = 1;
        $data = [];
        $headers = [];
        if (config('app.debug')) {
            $errors['file'] =  $exception->getFile() . ':' . $exception->getLine();
            // $errors['trace'] = $exception->getTraceAsString();
        }

        // Выбираем сообщение в зависимости от кода ошибки
        $responseMessage = ($code === 500) ? "Внутренняя ошибка сервера" : "Произошла ошибка";

        return new ApiResponse($data, $responseMessage, $code, $headers, $type, $errors);
    }
}
