<?php

namespace app\clients;

use app\classes\Interfaces\RandomizerClientInterface;

class RandomClient implements RandomizerClientInterface
{
    public function draw(array $tickets, float $winnerPercent): array
    {
        $winnersCount = floor(count($tickets) * $winnerPercent);
        return $this->drawFixed($tickets, $winnersCount);
    }

    public function drawFixed(array $tickets, int $winnersCount): array
    {
        if ($winnersCount <= 0 || empty($tickets)) {
            return [];
        }

        // Ограничиваем количество победителей количеством билетов
        $winnersCount = min($winnersCount, count($tickets));
        
        shuffle($tickets);
        $winners = [];
        
        for ($position = 1; $position <= $winnersCount; $position++) {
            $winners[] = [
                "winner_position" => $position,
                "ticket_number" => $tickets[$position - 1]
            ];
        }

        return $winners;
    }
}