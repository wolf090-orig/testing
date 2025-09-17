<?php

namespace app\clients;

use app\classes\Interfaces\MsTicketManagerInterface;

class MsTicketManagerFake implements MsTicketManagerInterface
{
    public function getAmountOfTickets(int $lotteryId): int
    {
        return 1000;
    }


}