<?php

namespace app\controller;

use app\services\BasketService;
use app\validations\AddCartRequest;
use Exception;
use support\Request;
use support\Response;
use think\exception\ValidateException;

class BasketController
{
    public BasketService $basketService;

    public function __construct()
    {
        $this->basketService = new BasketService();
    }

    public function getBasket(Request $request): Response
    {
        $user = $request->user();
        $this->basketService->setUserId($user->getId());
        $cart = $this->basketService->getBasket();

        return success($cart);
    }

    /**
     * @throws Exception
     */
    public function addBasket(Request $request): Response
    {
        $data = AddCartRequest::validated($request->all());

        // Если quantity указан, но ticket_numbers отсутствует, инициализируем его пустым массивом
        if (!isset($data['ticket_numbers']) && isset($data['quantity']) && $data['quantity'] > 0) {
            $data['ticket_numbers'] = [];
        }

        // Проверяем, что хотя бы один из параметров указан
        if (empty($data['ticket_numbers']) && (!isset($data['quantity']) || $data['quantity'] <= 0)) {
            $message = "ticket_numbers can not be empty if quantity is 0 or not specified";
            throw new ValidateException($message);
        }

        $user = $request->user();
        $this->basketService->setUserId($user->getId());
        $cart = $this->basketService->addBasket(
            $data['ticket_numbers'] ?? [],
            $data['lottery_id'],
            $data['quantity'] ?? 0
        );

        return success($cart);
    }

    public function payBasket(Request $request): Response
    {
        $user = $request->user();
        $this->basketService->setUserId($user->getId());
        $cart = $this->basketService->payBasket();

        return success($cart);
    }

    public function destroyBasket(Request $request): Response
    {
        $user = $request->user();
        $this->basketService->setUserId($user->getId());
        $ticketId = intval($request->input('ticket_id')) ?? null;
        $this->basketService->destroyBasket($ticketId);

        return success([]);
    }
}
