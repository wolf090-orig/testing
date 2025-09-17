<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\ContentNegotiator;

class SiteController extends Controller
{
    public function behaviors(): array
    {
        return [
            'contentNegotiator' => [
                'class' => ContentNegotiator::class,
                'formats' => [
                    'application/json' => Response::FORMAT_JSON,
                ],
            ],
        ];
    }

    public function actionError(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $exception = Yii::$app->errorHandler->exception;
        if ($exception !== null) {
            $statusCode = $exception->statusCode ?? 500;
            Yii::$app->response->statusCode = $statusCode;
            
            return [
                'result' => false,
                'error' => [
                    'code' => $statusCode,
                    'message' => $exception->getMessage(),
                ],
            ];
        }

        Yii::$app->response->statusCode = 500;
        return [
            'result' => false,
            'error' => [
                'code' => 500,
                'message' => 'Internal Server Error',
            ],
        ];
    }

    public function actionIndex(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        return [
            'message' => 'Loan API is running',
            'version' => '1.0.0',
            'endpoints' => [
                'POST /requests' => 'Create loan request',
                'GET /processor' => 'Process loan requests',
            ],
        ];
    }
}