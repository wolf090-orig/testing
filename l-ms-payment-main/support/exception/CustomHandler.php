<?php

namespace support\exception;

use app\classes\Responses\ApiResponse;
use app\enums\ResponseTypeEnum;
use Illuminate\Database\QueryException;
use support\Log;
use think\exception\ValidateException;
use Throwable;
use Webman\Exception\ExceptionHandler;
use Webman\Http\Request;
use Webman\Http\Response;

// use support\bootstrap\Sentry;

class CustomHandler extends ExceptionHandler
{
    public $dontReport = [
        BusinessException::class,
    ];

    /**
     * Логирует ошибку, если код не 500
     */
    protected function logError(Throwable $exception, int $code, ?Request $request = null): void
    {
        Log::error('[API ERROR]', [
            'code' => $code,
            'message' => $exception->getMessage(),
            'path' => $request?->path(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }

    public function report(Throwable $exception): void
    {
        if ($this->shouldntReport($exception)) {
            return;
        }
    }

    public function render(Request $request, Throwable $exception): Response
    {
        $errors = [];
        
        // Для API всегда возвращаем JSON, независимо от заголовков
        if ($exception instanceof ValidateException) {
            $code = 422;
            $errors[] = $exception->getError();

            return new ApiResponse([], "Произошла ошибка", $code, [], ResponseTypeEnum::ERROR, $errors);
        }

        if ($exception instanceof \app\exception\AuthException) {
            $code = $exception->getHttpStatusCode();
            $errors[] = [
                'code' => $exception->getCode(),
                'message' => $exception->getMessage(),
                'error' => $exception->getMessage()
            ];
            $headers = [];
            if (config('app.debug')) {
                $errors['file'] = $exception->getFile() . ':' . $exception->getLine();
                $errors['trace'] = $exception->getTraceAsString();
            }
            $this->logError($exception, $code, $request);
            return new ApiResponse([], "Произошла ошибка", $code, $headers, ResponseTypeEnum::ERROR, $errors);
        }

        // Универсальная обработка остальных ошибок
        $errorMessage = $exception->getMessage();
        $isDbError = $exception instanceof QueryException || strpos($errorMessage, 'SQLSTATE') !== false;
        $code = 500;

        if ($isDbError) {
            $errorMessage = 'Database error';
        }
        $errors[] = [
            'code' => $exception->getCode(),
            'message' => $errorMessage,
            'error' => $errorMessage
        ];
        $headers = [];
        if (config('app.debug')) {
            $errors['file'] = $exception->getFile() . ':' . $exception->getLine();
            $errors['trace'] = $exception->getTraceAsString();
        }
        
        // Выбираем сообщение в зависимости от кода ошибки
        $responseMessage = ($code === 500) ? "Внутренняя ошибка сервера" : "Произошла ошибка";
        
        $this->logError($exception, $code, $request);
        return new ApiResponse([], $responseMessage, $code, $headers, ResponseTypeEnum::ERROR, $errors);

    }
}
