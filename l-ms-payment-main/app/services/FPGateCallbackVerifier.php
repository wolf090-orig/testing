<?php

declare(strict_types=1);

namespace app\services;

/**
 * Stub для верификации callback от FPGate
 */
class FPGateCallbackVerifier
{
    private string $secret;

    public function __construct(string $secret)
    {
        $this->secret = $secret;
    }

    public function verifyCallback(array $callbackData): bool
    {
        // TODO: Добавить реальную верификацию подписи
        return true;
    }

    public function mapFPGateStatus(string $fpgateStatus): string
    {
        // TODO: Добавить мапинг статусов FPGate -> внутренние статусы
        return 'processing';
    }

    public function extractFeeAmount(array $callbackData): ?int
    {
        // TODO: Добавить извлечение fee из callback
        return null;
    }
}
