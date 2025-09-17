<?php

namespace app\controller;

use app\dto\GetUserTicketDTO;
use app\services\TicketService;
use app\validations\GetLotteryInfoRequest;
use app\validations\GetLotteryInfoWithoutIdRequest;
use app\validations\GetTicketsRequest;
use app\validations\GetUserTicketRequest;
use app\validations\GetUserTicketsRequest;
use support\Request;
use support\Response;
use Webman\Exception\NotFoundException;

class LotteryController
{
    public TicketService $ticketService;

    public function __construct()
    {
        $this->ticketService = new TicketService();
    }

    public function getLotteries(Request $request): Response
    {
        $data = GetLotteryInfoWithoutIdRequest::validated($request->all());
        $lotteryType = $data['lottery_type'] ?? null;
        $status = $data['status'] ?? 'active';
        $countryCode = $data['country_code'] ?? null;

        $lotteries = $this->ticketService->getLotteries($lotteryType, $status, $countryCode);

        return success($lotteries);
    }

    public function getTickets(Request $request): Response
    {
        $data = GetTicketsRequest::validated($request->all());
        $tickets = $this->ticketService->getTickets($data);
        return success($tickets);
    }

    public function getLotteryInfo(Request $request): Response
    {
        $data = GetLotteryInfoRequest::validated($request->all());
        $lotteryData = $this->ticketService->getLotteryInfo($data['lottery_id']);
        return success($lotteryData);
    }

    public function getUserTickets(Request $request): Response
    {
        $data = GetUserTicketsRequest::validated($request->all());
        $userId = $request->user()->getId();
        $tickets = $this->ticketService->getUserTickets($userId, $data);

        return success($tickets);
    }

    public function getUserTicket(Request $request, int $ticketId): Response
    {
        $data = GetUserTicketRequest::validated($request->all());
        $data['user_id'] = $request->user()->getId();
        $data['ticket_id'] = $ticketId;

        try {
            $ticket = $this->ticketService->getUserTicket(new GetUserTicketDTO($data));

            return success($ticket);
        } catch (NotFoundException $e) {
            return error([$e->getMessage()], 404);
        }
    }

    /**
     * Получение статистики билетов пользователя
     */
    public function getUserStatistics(Request $request): Response
    {
        $userId = $request->user()->getId();
        $statistics = $this->ticketService->getUserStatistics($userId);

        return success($statistics);
    }
}
