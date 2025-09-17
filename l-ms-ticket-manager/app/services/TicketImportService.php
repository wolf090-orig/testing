<?php

namespace app\services;

use app\classes\Interfaces\TicketRepositoryInterface;
use app\dto\TicketImportMessageDTO;
use app\exceptions\TicketImportMessageProcessingException;
use app\exceptions\TicketImportMessageValidationException;
use Exception;
use Psr\Log\LoggerInterface;
use support\Container;
use support\Log;

class TicketImportService
{
    private LoggerInterface $logger;
    private TicketRepositoryInterface $ticketRepository;

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?: Log::channel('command_import_winner_tickets');
        $this->ticketRepository = Container::get(TicketRepositoryInterface::class);
    }

    /**
     * Проверяет валидность сообщения.
     *
     * @param TicketImportMessageDTO $messageDTO Сообщение
     * @return bool
     * @throws TicketImportMessageValidationException Если сообщение невалидно
     */
    public function validateMessage(TicketImportMessageDTO $messageDTO): bool
    {
        $body = $messageDTO->getBody();

        $this->logger->info('TicketImportService: Проверка валидности сообщения', [
            'body' => json_encode($body),
        ]);

        if (!isset($body['lottery_id'], $body['tickets']) || !is_array($body['tickets'])) {
            $this->logger->error('TicketImportService: Неверный формат данных сообщения', [
                'body' => json_encode($body),
            ]);
            throw new TicketImportMessageValidationException('Неверный формат данных сообщения');
        }

        foreach ($body['tickets'] as $ticket) {
            if (!isset($ticket['ticket_number'], $ticket['winner_position'])) {
                $this->logger->error('TicketImportService: Неверный формат данных билета', [
                    'ticket' => json_encode($ticket),
                ]);
                throw new TicketImportMessageValidationException('Неверный формат данных билета');
            }
        }

        $this->logger->info('TicketImportService: Сообщение валидно');

        return true;
    }

    /**
     * Обрабатывает сообщение и сохраняет выигрышные билеты.
     *
     * @param TicketImportMessageDTO $messageDTO Сообщение
     * @return void
     * @throws TicketImportMessageProcessingException Если произошла ошибка при обработке сообщения
     */
    public function processMessage(TicketImportMessageDTO $messageDTO): void
    {
        $body = $messageDTO->getBody();

        $this->logger->info('TicketImportService: обработка сообщения', [
            'body' => json_encode($body),
        ]);

        try {
            $lotteryId = $body['lottery_id'];
            if (!$this->ticketRepository->canDrawLottery($lotteryId)) {
                $this->logger->info('TicketImportService: мы не можем разыграть лотерею 
                - останавливаем обработку сообщения');
                return;
            }
            $ticketNumbers = array_column($body['tickets'], 'ticket_number');

            $this->logger->info('TicketImportService: Количество билетов в сообщении', [
                'count' => count($ticketNumbers),
            ]);

            $userTickets = $this->ticketRepository->findUserTicketsByNumbers($ticketNumbers, $lotteryId);

            $this->logger->info('TicketImportService: Количество найденных билетов в БД с указанными номерами', [
                'count' => count($userTickets),
            ]);

            $winnerTickets = [];
            $notFoundTickets = [];

            foreach ($body['tickets'] as $ticket) {
                $ticketNumber = $ticket['ticket_number'];
                $winnerPosition = $ticket['winner_position'];

                if (!isset($userTickets[$ticketNumber])) {
                    $notFoundTickets[] = $ticketNumber;
                    continue;
                }

                $userTicket = $userTickets[$ticketNumber];
                $winnerTickets[] = [
                    'user_ticket_purchase_id' => $userTicket->id,
                    'user_id' => $userTicket->user_id,
                    'lottery_id' => $lotteryId,
                    'payout_amount' => 0, // Обновим позже из LeaderBoardService
                    'payout_currency_id' => null, // Обновим позже из LeaderBoardService
                    'winner_position' => $winnerPosition
                ];
            }

            if (!empty($notFoundTickets)) {
                $this->logger->warning('TicketImportService: Билеты с номерами не найдены в базе данных', [
                    'not_found_tickets' => $notFoundTickets,
                ]);
            }

            if (!empty($winnerTickets)) {
                // Используем LeaderBoardService для получения сумм выигрыша
                $leaderBoardService = new LeaderBoardService($lotteryId);
                $leaderBoard = $leaderBoardService->getLeaderBoard();
                $prizeDetails = $leaderBoard['prize_details'];

                // Обновляем payout_amount и payout_currency_id в winnerTickets
                foreach ($winnerTickets as &$winnerTicket) {
                    foreach ($prizeDetails as $prizeDetail) {
                        if ($winnerTicket['winner_position'] == $prizeDetail['position']) {
                            $winnerTicket['payout_amount'] = $prizeDetail['amount'];
                            $winnerTicket['payout_currency_id'] = $prizeDetail['currency_id'] ?? 1; // По умолчанию валюта с ID 1
                            break;
                        }
                    }
                }

                $this->logger->info('TicketImportService: вызов метода репозитория с параметрами', [
                    'lottery_id' => $lotteryId,
                    'winner_tickets' => $winnerTickets,
                ]);

                $this->ticketRepository->saveWinnerTicketsBatch($winnerTickets, $lotteryId);
                $this->logger->info('TicketImportService: Пачка выигрышных билетов сохранена', [
                    'count' => count($winnerTickets),
                ]);
                $this->ticketRepository->lotteryDrawn($lotteryId);
            } else {
                $this->logger->info('TicketImportService: Нет выигрышных билетов для сохранения');
            }
        } catch (Exception $e) {
            $this->logger->error('TicketImportService: Ошибка обработки сообщения', [
                'error' => $e->getMessage(),
            ]);
            throw new TicketImportMessageProcessingException('Ошибка обработки сообщения: ' . $e->getMessage(), 0, $e);
        }
    }
}
