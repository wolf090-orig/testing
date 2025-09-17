<?php

declare(strict_types=1);

namespace app\factories;

use app\interfaces\PaymentGatewayInterface;
use app\clients\FPGateApiClient;
use InvalidArgumentException;

/**
 * Фабрика для создания клиентов платежных шлюзов
 */
class PaymentGatewayFactory
{
    /**
     * Создает клиент для PayIn операций
     */
    public static function createPayInClient(): PaymentGatewayInterface
    {
        $gatewayType = config('payment.default_gateway');

        return match ($gatewayType) {
            'fpgate' => new FPGateApiClient(config('payment.fpgate_payin')),
            default => throw new InvalidArgumentException("Неподдерживаемый шлюз: $gatewayType")
        };
    }

    /**
     * Создает клиент для PayOut операций
     */
    public static function createPayOutClient(): PaymentGatewayInterface
    {
        $gatewayType = config('payment.default_gateway');

        return match ($gatewayType) {
            'fpgate' => new FPGateApiClient(config('payment.fpgate_payout')),
            default => throw new InvalidArgumentException("Неподдерживаемый шлюз: $gatewayType")
        };
    }

    /**
     * Создает клиент для конкретного шлюза и операции
     */
    public static function createClient(string $gateway, string $operation): PaymentGatewayInterface
    {
        if ($gateway === 'fpgate') {
            $configKey = $operation === 'payin' ? 'payment.fpgate_payin' : 'payment.fpgate_payout';
            return new FPGateApiClient(config($configKey));
        }

        throw new InvalidArgumentException("Неподдерживаемый шлюз: $gateway");
    }
}
