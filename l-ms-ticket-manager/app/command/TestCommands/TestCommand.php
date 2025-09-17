<?php

namespace app\command\TestCommands;

use app\dto\TicketImportMessageDTO;
use app\exceptions\TicketImportMessageProcessingException;
use app\exceptions\TicketImportMessageValidationException;
use app\services\TicketImportService;
use Psr\Log\LoggerInterface;
use support\Log;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestCommand extends Command
{
    protected static $defaultName = 'TestCommand';
    protected static $defaultDescription = 'TestCommand';

    private TicketImportService $ticketImportService;
    private LoggerInterface $logger;

    public function __construct()
    {
        $this->logger = Log::channel('command_import_winner_tickets');
        $this->ticketImportService = new TicketImportService($this->logger);
        parent::__construct();
    }

    /**
     * @return void
     */
    protected function configure()
    {
        $this->addArgument('name', InputArgument::OPTIONAL, 'Name description');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');
        $output->writeln('Hello TestCommand');

        // Пример сообщения Kafka с реальными номерами билетов
        $messageData = [
            'lottery_id' => 1,
            'lottery_name' => 'RU Daily Fixed 30.06.2025',
            'draw_date' => '2025-06-30T18:00:00Z',
            'tickets' => [
                [
                    'ticket_number' => 'RU0000601_L1',
                    'winner_position' => 1
                ],
                [
                    'ticket_number' => 'RU0000602_L1',
                    'winner_position' => 2
                ],
                [
                    'ticket_number' => 'RU0000603_L1',
                    'winner_position' => 3
                ],
                [
                    'ticket_number' => 'RU0000604_L1',
                    'winner_position' => 4
                ],
                [
                    'ticket_number' => 'RU0000605_L1',
                    'winner_position' => 5
                ],
            ]
        ];

        $headers = []; // Пример заголовков сообщения
        $messageDTO = new TicketImportMessageDTO($messageData, $headers);

        try {
            if ($this->ticketImportService->validateMessage($messageDTO)) {
                $this->ticketImportService->processMessage($messageDTO);
            }
            $output->writeln('Сообщение обработано успешно');
        } catch (TicketImportMessageValidationException $e) {
            $output->writeln('Сообщение не валидно: ' . $e->getMessage());
            $output->writeln('Body: ' . json_encode($messageData));
        } catch (TicketImportMessageProcessingException $e) {
            $output->writeln('Ошибка обработки сообщения: ' . $e->getMessage());
            $output->writeln('Body: ' . json_encode($messageData));
        }

        return self::SUCCESS;
    }
}
