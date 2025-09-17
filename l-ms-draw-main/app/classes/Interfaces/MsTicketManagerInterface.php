<?php

namespace app\classes\Interfaces;

interface MsTicketManagerInterface
{
    public function getAmountOfTickets(int $lotteryId): int;
}