<?php

namespace app\clients;

// make client to detect all tickets
use app\classes\Interfaces\MsTicketManagerInterface;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use support\Log;

class MsTicketManagerClient implements MsTicketManagerInterface
{
    public const TICKET_URI_PREFIX = "/api/v1/ticket";
    public Client $client;
    public LoggerInterface $logger;

    public function __construct()
    {
        $this->logger = Log::channel('default');
        $config = config('integrations.ms_ticket_manager');
        $this->client = new Client([
            'base_uri' => $config['base_uri'],
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ]
        ]);
    }

    public function getAmountOfTickets(int $lotteryId): int
    {
        $this->logger->info("Начинаем запрос на МС УП для сверки количества проданных билетов:", ['lotteryId' => $lotteryId]);
        $response = $this->client->get(self::TICKET_URI_PREFIX . '/info', [
            'query' => [
                'lottery_id' => $lotteryId
            ]
        ]);

        $data = json_decode($response->getBody()->getContents(), true);
        $this->logger->info("Данные от МС УП по лотереи $lotteryId :" . json_encode($data));
        $ticketsAmount = $data['data']['paid_tickets_count'] ?? 0;
        return $ticketsAmount;
    }


}