<?php

declare(strict_types=1);

namespace app\controller;

use app\classes\Responses\ApiResponse;
use app\enums\ResponseTypeEnum;
use app\services\PaymentService;
use app\validations\PaymentRequest;
use support\Request;

/**
 * Контроллер для создания платежей
 */
class PaymentController
{
    private PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }



    /**
     * Создание пополнения (PayIn)
     * 
     * POST /api/v1/payments/payin
     * С Internal API middleware
     */
    public function createPayIn(Request $request)
    {
        $data = PaymentRequest::validated($request->post());

        $result = $this->paymentService->createPayIn($data);

        if ($result['success']) {
            return new ApiResponse($result['data'], 'Платеж создан успешно', 200, [], ResponseTypeEnum::SUCCESS);
        } else {
            return new ApiResponse([], "Произошла ошибка", 400, [], ResponseTypeEnum::ERROR, [
                'code' => $result['error'],
                'details' => $result['details']
            ]);
        }
    }


}
