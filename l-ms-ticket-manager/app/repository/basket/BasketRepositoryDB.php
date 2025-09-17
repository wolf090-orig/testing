<?php

namespace app\repository\basket;

use app\classes\Interfaces\BasketRepositoryInterface;
use app\classes\Interfaces\MsPaymentInterface;
use app\exceptions\DestroyBasketRepositoryException;
use app\exceptions\PayBasketRepositoryException;
use app\model\Basket;
use app\model\CancelReasons;
use app\model\LotteryNumber;
use app\model\PaymentStatus;
use app\model\UserTicketPurchase;
use Carbon\Carbon;
use Exception;
use support\Container;
use support\Db;
use support\Log;

class BasketRepositoryDB implements BasketRepositoryInterface
{
    const int MAX_TICKETS_COUNT = 20;

    /**
     * Добавляет билеты в корзину пользователя.
     *
     * @param int $userId Идентификатор пользователя.
     * @param array $ticketNumberIds Массив идентификаторов номеров билетов.
     * @param int $lotteryId Идентификатор лотереи.
     *
     * @return array Детали корзины.
     * @throws Exception Если возникла ошибка при добавлении билетов.
     */
    public function addBasket(int $userId, array $ticketNumberIds, $lotteryId): array
    {
        if (empty($ticketNumberIds)) {
            throw new Exception("Билеты для добавление в корзину не могут быть пустыми", 500);
        }

        $ticketModel = $this->validateLotteryForBasket($lotteryId);
        $tickets = $this->getValidTickets($ticketModel, $ticketNumberIds, $lotteryId);
        $basket = $this->findActiveBasket($userId);
        $tickets = $this->adjustTicketsForBasketSize($basket, $tickets);

        $this->addTicketsToBasket($basket, $tickets, $ticketModel);

        return $this->getBasketDetails($basket);
    }

    private function validateLotteryForBasket(int $lotteryId): LotteryNumber
    {
        $lottery = LotteryNumber::find($lotteryId);

        if (empty($lottery)) {
            throw new Exception("Не можем найти лотерею", 400);
        }

        // Проверяем активность лотереи
        if (!$lottery->is_active) {
            throw new Exception("Лотерея неактивна", 400);
        }

        // Проверяем что лотерея не разыграна
        if ($lottery->is_drawn) {
            throw new Exception("Лотерея уже разыграна", 400);
        }

        // Проверяем период продаж (только если указаны даты)
        if ($lottery->end_date && Carbon::now()->gt($lottery->end_date)) {
            throw new Exception("Время продаж для данной лотереи закончилось", 400);
        }

        if ($lottery->start_date && Carbon::now()->lt($lottery->start_date)) {
            throw new Exception("Время продаж для данной лотереи не началось", 400);
        }

        list(, $lotteryPartitionTableName) = $lottery->getTablePartitionName();

        return LotteryNumber::getLotteryPartitionModel($lotteryPartitionTableName);
    }

    private function findActiveBasket(int $userId, bool $forceCreate = true): ?Basket
    {
        $basket = Basket::where('user_id', $userId)
            ->with('tickets')
            ->where("end_date", ">", Carbon::now())
            ->whereNull("cancel_reason_id")
            ->latest()
            ->first();

        if (empty($basket) && $forceCreate) {
            // let's create it
            $basket = Basket::create([
                'start_date' => Carbon::now(),
                'end_date' => Carbon::now()->addMinutes(15),
                'user_id' => $userId,
            ]);
        }
        return $basket;
    }

    /**
     * @throws PayBasketRepositoryException
     * @throws Exception
     */
    public function payBasket(int $userId): array
    {
        // get active cart to payout
        $basket = $this->findActiveBasket($userId);

        $tickets = $basket->tickets()->get();

        if ($tickets->count() < 1) {
            throw new Exception("в корзине нет билетов для оплаты");
        }

        $purchasesData = [];
        $lotteryId = null;
        $ticketNumbers = [];

        // Получаем цену билета один раз
        $firstTicket = $tickets->first();

        $lottery = LotteryNumber::with('price')->find($firstTicket['lottery_id']);

        $ticketPrice = $lottery->getPrice();
        $currencyId = $lottery->price->currency_id;
        $totalAmount = $ticketPrice * $tickets->count();

        foreach ($tickets as $ticket) {
            $lotteryId = $ticket['lottery_id'];
            $ticketNumbers[] = $ticket['ticket_id'];

            // Формируем данные для вставки в user_ticket_purchases
            $purchasesData[] = [
                'user_id' => $userId,
                'basket_id' => $basket->id,
                'ticket_id' => $ticket['ticket_id'],
                'lottery_id' => $ticket['lottery_id'],
                'purchase_amount' => $ticketPrice,
                'purchase_currency_id' => $currencyId,
                'purchased_at' => Carbon::now(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];
        }

        if (empty($purchasesData)) {
            return [];
        }

        // Создаем платеж через ms-payment
        $msPayment = Container::make(MsPaymentInterface::class);
        $internalOrderId = "basket_{$basket->id}_" . time();

        Log::channel('ms_payment_integration')->info('Creating payment for basket', [
            'basket_id' => $basket->id,
            'user_id' => $userId,
            'total_amount' => $totalAmount,
            'internal_order_id' => $internalOrderId
        ]);

        $paymentResult = $msPayment->createPayIn(
            $internalOrderId,
            $userId,
            $totalAmount, // сумма в копейках
            'RUB',
            'card', // по умолчанию карта
            [
                'basket_id' => $basket->id,
                'lottery_id' => $lotteryId,
                'tickets_count' => count($ticketNumbers)
            ]
        );

        if (!$paymentResult['success']) {
            Log::channel('ms_payment_integration')->error('Payment creation failed', [
                'basket_id' => $basket->id,
                'user_id' => $userId,
                'internal_order_id' => $internalOrderId,
                'error' => $paymentResult['error'] ?? 'unknown',
                'details' => $paymentResult['details'] ?? 'no details'
            ]);

            throw new PayBasketRepositoryException(
                "Не удалось создать платеж: " . ($paymentResult['details'] ?? 'неизвестная ошибка')
            );
        }

        Log::channel('ms_payment_integration')->info('Payment created successfully', [
            'basket_id' => $basket->id,
            'user_id' => $userId,
            'internal_order_id' => $internalOrderId,
            'payment_data' => $paymentResult['data']
        ]);

        Db::beginTransaction();
        try {
            // Сохраняем ID транзакции в корзину
            $basket->transaction_id = $internalOrderId;
            $basket->save();

            // Вставляем покупки в партиционированную таблицу user_ticket_purchases
            Db::table('user_ticket_purchases')->insert($purchasesData);

            // Помечаем билеты как оплаченные в партиции лотереи
            $ticketModel = $this->validateLotteryForBasket($lotteryId);

            $ticketModel->whereIn('id', $ticketNumbers)->update([
                'is_paid' => true,
            ]);

            // Проставление признака оплаты и закрытия корзины
            $cancelReason = CancelReasons::where('name', CancelReasons::PAYMENT_SUCCESS)->first();
            $paymentStatus = PaymentStatus::where('name', PaymentStatus::PAID)->first();

            $basket->cancel_reason_id = $cancelReason->id;
            $basket->payment_status_id = $paymentStatus->id;
            $basket->is_payment_lock = false;
            $basket->save();

            Log::channel('ms_payment_integration')->info('Basket payment completed', [
                'basket_id' => $basket->id,
                'user_id' => $userId,
                'tickets_count' => count($purchasesData),
                'total_amount' => $totalAmount,
                'internal_order_id' => $internalOrderId
            ]);

            Db::commit();

            // Возвращаем данные с информацией о платеже
            return array_merge($purchasesData, [
                'payment_info' => [
                    'internal_order_id' => $internalOrderId,
                    'payment_data' => $paymentResult['data']
                ]
            ]);

        } catch (Exception $e) {
            Db::rollBack();
            Log::channel('ms_payment_integration')->error('Payment basket transaction failed', [
                'basket_id' => $basket->id,
                'user_id' => $userId,
                'internal_order_id' => $internalOrderId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new PayBasketRepositoryException(
                "Не удалось проставить признак оплаты к билетам: " . $e->getMessage()
            );
        }
    }

    public function getBasket(int $userId): array
    {
        $basket = $this->findActiveBasket($userId, false);
        if (empty($basket)) {
            return [
                "id" => 0,
                "tickets" => [],
                "total_price" => 0,
                "start_date" => Carbon::now()->toISOString(),
                "end_date" => Carbon::now()->addMinutes(15)->toISOString(),
            ];
        }

        $details = $this->getBasketDetails($basket);

        return $details;
    }

    /**
     * @throws DestroyBasketRepositoryException
     * @throws Exception
     */
    public function destroyBasket(int $userId, ?int $ticketId): void
    {
        $basket = $this->findActiveBasket($userId, false);

        if (empty($basket)) {
            return;
        }

        try {
            if ($ticketId) {
                $basket->removeTicket($ticketId);
            } else {
                $cancelReason = CancelReasons::where('name', CancelReasons::CANCELED_BY_USER)->first();
                $basket->closeBasket($cancelReason->id);
            }
        } catch (Exception $e) {
            throw new DestroyBasketRepositoryException("Ошибка удаления из корзины: " . $e->getMessage());
        }
    }

    /**
     * Получает указанное количество случайных билетов из заданной лотереи, которые не зарезервированы.
     *
     * @param int $lotteryId Идентификатор лотереи.
     * @param int $quantity  Количество случайных билетов, которые необходимо получить.
     *
     * @return array Массив билетов, где каждый билет представлен в виде ассоциативного массива с ключами 'id' и 'ticket_number'.
     * @throws Exception Если лотерея с указанным идентификатором не найдена.
     */
    public function getRandomTickets(int $lotteryId, int $quantity): array
    {
        $lottery = LotteryNumber::where('id', $lotteryId)->first();
        if (empty($lottery)) {
            throw new Exception("Лотерея не найдена");
        }

        list(, $lotteryTable) = $lottery->getTablePartitionName();

        return LotteryNumber::getLotteryPartitionModel($lotteryTable)
            ->where('lottery_id', $lotteryId)
            ->where('is_reserved', false)
            ->where('is_paid', false)
            ->inRandomOrder()
            ->limit($quantity)
            ->get(['id', 'ticket_number'])
            ->toArray();
    }

    /**
     * Извлекает действительные билеты на основе предоставленных идентификаторов билетов и идентификаторов лотереи.
     * Отфильтровывает билеты, которые уже оплачены или зарезервированы. Форматирует билеты для добавления в корзину.
     *
     * @param LotteryNumber $ticketModel
     * @param array $ticketNumberIds
     * @param int $lotteryId Идентификатор лотереи.
     *
     * @return array Массив действительных билетов, отформатированный для добавления в корзину.
     */
    private function getValidTickets(LotteryNumber $ticketModel, array $ticketNumberIds, int $lotteryId): array
    {
        return $ticketModel->whereIn('id', $ticketNumberIds)
            ->where('is_paid', false)
            ->where('is_reserved', false)
            ->get(['id'])
            ->map(function ($ticket) use ($lotteryId) {
                return [
                    'ticket_id' => $ticket->id,
                    'lottery_id' => $lotteryId,
                ];
            })
            ->toArray();
    }

    private function adjustTicketsForBasketSize(?Basket $basket, array $tickets): array
    {
        if (empty($basket)) {
            return array_slice($tickets, 0, self::MAX_TICKETS_COUNT);
        }

        $basketTicketsCount = $basket->tickets()->count();
        $maxNewTickets = self::MAX_TICKETS_COUNT - $basketTicketsCount;

        if ($maxNewTickets <= 0) {
            throw new Exception("Корзина заполнена. Максимальное количество билетов: " . self::MAX_TICKETS_COUNT);
        }

        return array_slice($tickets, 0, $maxNewTickets);
    }

    private function addTicketsToBasket(Basket $basket, array $tickets, LotteryNumber $ticketModel): void
    {
        if (empty($tickets)) {
            throw new Exception("Нет доступных билетов для добавления в корзину");
        }

        // Резервируем билеты
        $ticketIds = array_column($tickets, 'ticket_id');
        $ticketModel->whereIn('id', $ticketIds)->update(['is_reserved' => true]);

        // Добавляем билеты в корзину
        $basket->addTicket($tickets);
    }

    private function getBasketDetails(Basket $basket): array
    {
        return [
            "id" => $basket->id,
            "tickets" => $basket->getDTOTickets(),
            "total_price" => $basket->getTotalPrice(),
            "start_date" => $basket->start_date->toISOString(),
            "end_date" => $basket->end_date->toISOString(),
        ];
    }
}
