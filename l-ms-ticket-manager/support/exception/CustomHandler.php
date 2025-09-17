<?php

namespace support\exception;

use app\classes\Responses\ApiResponse;
use app\enums\ResponseTypeEnum;
use app\exception\TelegramAuthException;
// use support\bootstrap\Sentry;
use support\Log;
use think\exception\ValidateException;
use Throwable;
use Webman\Exception\ExceptionHandler;
use Webman\Http\Request;
use Webman\Http\Response;
use Illuminate\Database\QueryException;

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

        // Специальная обработка для TelegramAuthException
        if ($exception instanceof TelegramAuthException) {
            $logger = Log::channel('telegram_auth');
            $errorData = $exception->getErrorData();
            $errorDataJson = !empty($errorData) ? json_encode($errorData) : '{}';

            $logger->error('[' . self::class . '] Ошибка авторизации Telegram: ' . $exception->getMessage(), [
                'код' => $exception->getCode(),
                'данные' => $errorDataJson,
                'запрос' => $logs,
                'файл' => $exception->getFile() . ':' . $exception->getLine()
            ]);

            return;
        }

//        Log::channel('stdout')->error($logs . PHP_EOL . $exception);

        // Sentry::captureException($exception);
    }

    public function render(Request $request, Throwable $exception): Response
    {
        $errors = [];
        if ($request->expectsJson()) {
            if ($exception instanceof ValidateException) {
                $code = 422;
                $errors[] = $exception->getError();
                return new ApiResponse([], "Validation error", $code, [], ResponseTypeEnum::ERROR, $errors);
            }

            // Специальная обработка для TelegramAuthException
            else if ($exception instanceof TelegramAuthException) {
                $code = $exception->getCode();
                $errorData = $exception->getErrorData();

                $errors[] = [
                    'code' => $code,
                    'message' => $exception->getMessage(),
                    'error' => $exception->getMessage(),
                    'data' => $errorData
                ];

                // Записываем в лог, что возвращаем на фронт
                $logger = Log::channel('telegram_auth');
                $logger->info('[' . self::class . '] Возвращаем на фронт:', [
                    'status_code' => $code,
                    'message' => $exception->getMessage(),
                    'payload' => json_encode($errors)
                ]);
            }
            else {
                // Для SQL ошибок всегда используем код 500
                if ($exception instanceof QueryException) {
                    $code = 500;
                } else {
                    // Используем код из исключения, если он находится в диапазоне HTTP-статусов
                    $exceptionCode = $exception->getCode();
                    if ($exceptionCode >= 100 && $exceptionCode < 600) {
                        $code = $exceptionCode;
                    } else {
                        $code = 500;
                    }
                }

                // Проверяем, содержит ли сообщение об ошибке строку SQLSTATE
                $errorMessage = $exception->getMessage();
                if (strpos($errorMessage, 'SQLSTATE') !== false) {
                    $errorMessage = 'Database error';
                }

                $errors[] = [
                    'code' => $exception->getCode(),
                    'message' => $errorMessage,
                    'error' => $errorMessage
                ];
            }

            $type = ResponseTypeEnum::ERROR;
            $data = [];
            $headers = [];
            if (config('app.debug')) {
                $errors['file'] =  $exception->getFile() . ':' . $exception->getLine();
                $errors['trace'] = $exception->getTraceAsString();
            }

            return new ApiResponse($data, "error happened", $code, $headers, $type, $errors);
        }

        return parent::render($request, $exception);
    }
}
