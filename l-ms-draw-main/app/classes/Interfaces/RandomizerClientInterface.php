<?php

namespace app\classes\Interfaces;

interface RandomizerClientInterface
{
    public function draw(array $tickets, float $winnerPercent): array;

    /**
     * Розыгрыш с фиксированным количеством победителей
     */
    public function drawFixed(array $tickets, int $winnersCount): array;
}
