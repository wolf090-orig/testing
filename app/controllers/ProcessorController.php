<?php

namespace app\controllers;

use app\services\LoanService;
use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use yii\filters\ContentNegotiator;

class ProcessorController extends Controller
{
    private LoanService $loanService;

    public function __construct($id, $module, $config = [])
    {
        $this->loanService = new LoanService();
        parent::__construct($id, $module, $config);
    }

    public function behaviors(): array
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'process' => ['GET'],
                ],
            ],
            'contentNegotiator' => [
                'class' => ContentNegotiator::class,
                'formats' => [
                    'application/json' => Response::FORMAT_JSON,
                ],
            ],
        ];
    }

    public function beforeAction($action): bool
    {
        $this->enableCsrfValidation = false;
        return parent::beforeAction($action);
    }

    public function actionProcess(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        try {
            $delay = (int) Yii::$app->request->get('delay', 0);
            
            if ($delay < 0) {
                $delay = 0;
            }

            Yii::info("Запуск обработки займов с задержкой: {$delay} секунд");
            
            $result = $this->loanService->processLoanRequests($delay);
            
            if ($result['success']) {
                Yii::info("Обработка займов завершена. Обработано: {$result['processed_count']} заявок");
                
                if (!empty($result['errors'])) {
                    Yii::warning('Некоторые заявки не удалось обработать', $result['errors']);
                }
                
                Yii::$app->response->statusCode = 200;
                return ['result' => true];
            } else {
                Yii::error('Обработка займов не удалась');
                Yii::$app->response->statusCode = 500;
                return ['result' => false];
            }
            
        } catch (\Exception $e) {
            Yii::error('Ошибка обработки заявок на займ: ' . $e->getMessage());
            Yii::$app->response->statusCode = 500;
            return ['result' => false];
        }
    }
}