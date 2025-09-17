<?php

namespace app\controllers;

use app\services\LoanService;
use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use yii\filters\ContentNegotiator;

class RequestsController extends Controller
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
                    'create' => ['POST'],
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

    public function actionCreate(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        try {
            $requestData = Yii::$app->request->getBodyParams();
            
            if (empty($requestData)) {
                $rawBody = Yii::$app->request->getRawBody();
                $requestData = json_decode($rawBody, true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    Yii::$app->response->statusCode = 400;
                    return ['result' => false];
                }
            }

            $validationErrors = $this->loanService->validateLoanRequestData($requestData);
            if (!empty($validationErrors)) {
                Yii::$app->response->statusCode = 400;
                return ['result' => false];
            }

            $result = $this->loanService->createLoanRequest($requestData);
            
            if ($result['success']) {
                Yii::$app->response->statusCode = 201;
                return [
                    'result' => true,
                    'id' => $result['id'],
                ];
            } else {
                Yii::$app->response->statusCode = 400;
                return ['result' => false];
            }
            
        } catch (\Exception $e) {
            Yii::error('Ошибка создания заявки на займ: ' . $e->getMessage());
            Yii::$app->response->statusCode = 400;
            return ['result' => false];
        }
    }
}