<?php

namespace app\dto;

class GetUserTicketDTO
{
    public int $userId;
    public int $ticketId;
    public bool $withLeaderboard = false;

    public function __construct(array $data)
    {
        $this->userId = $data['user_id'];
        $this->ticketId = $data['ticket_id'];
        $this->withLeaderboard = $data['with_leaderboard'] ?? false;
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'ticket_id' => $this->ticketId,
            'with_leaderboard' => $this->withLeaderboard,
        ];
    }
}