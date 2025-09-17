<?php

namespace app\classes\Interfaces;

interface MsPaymentInterface
{
    /**
     * Создает пополнение (PayIn)
     *
     * @param string $internalOrderId Внутренний ID заказа
     * @param int $userId ID пользователя
     * @param int $amount Сумма в копейках
     * @param string $currency Валюта (RUB)
     * @param string $paymentMethod Метод оплаты (card, sbp, cross_border_sbp)
     * @param array $details Дополнительные данные
     * @param array|null $receipt Данные чека (опционально)
     * @return array Результат создания платежа
     */
    public function createPayIn(
        string $internalOrderId,
        int $userId,
        int $amount,
        string $currency,
        string $paymentMethod,
        array $details = [],
        ?array $receipt = null
    ): array;
}
