<?php

namespace app\command\Tickets\Import;

use app\dto\TicketImportMessageDTO;
use app\exceptions\TicketImportMessageProcessingException;
use app\exceptions\TicketImportMessageValidationException;
use app\libraries\kafka\consumers\Consumer;
use app\libraries\kafka\messages\contracts\KafkaConsumerMessage;
use app\services\TicketImportService;
use Psr\Log\LoggerInterface;
use support\Log;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportWinnerTicketsCommand extends Command
{
    protected static $defaultName = 'import_winner_tickets:run';
    protected static $defaultDescription = 'Импорт выигрышных билетов из сообщений Kafka';

    public function run(InputInterface $input, OutputInterface $output): int
    {
        return $this->execute($input, $output);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var LoggerInterface $logger */
        $logger = Log::channel('command_import_winner_tickets');
        $logger->info("Запуск команды импорта выигрышных билетов");

        $ticketImportService = new TicketImportService($logger);
        $topicName = config('kafka.lottery_draw_results_topic');

        while (true) {
            $consumer = Consumer::createFromConfigKey(
                'tickets',
                [$topicName],
                $logger
            )
                ->withHandler(function (KafkaConsumerMessage $message) use ($logger, $ticketImportService) {
                    /** @var Consumer $this */
                    $lastOffset = $this->getLastOffset($message->getTopicName(), $message->getPartition());
                    $currentOffset = $message->getOffset();
                    $cntMessagesInTopicPartition = $lastOffset + 1;

                    $logMessage = "Обработка сообщения с офсетом $currentOffset.\n" .
                        "Всего сообщений в топике - $cntMessagesInTopicPartition; " .
                        "Партиция - {$message->getPartition()}.\n" .
                        "Последнее сообщение в топике - $lastOffset";

                    $logger->info($logMessage);

                    $body = $message->getBody();
                    $headers = $message->getHeaders();

                    $logger->info(
                        'Прочитано сообщение: ' . json_encode([
                            'body' => $body,
                            'headers' => $headers
                        ])
                    );

                    $messageDTO = new TicketImportMessageDTO($body, $headers);

                    try {
                        if ($ticketImportService->validateMessage($messageDTO)) {
                            $ticketImportService->processMessage($messageDTO);
                        }
                        $logger->info('Сообщение обработано успешно');
                    } catch (TicketImportMessageValidationException $e) {
                        $logger->error('Сообщение не валидно', [
                            'message' => $e->getMessage(),
                            'body' => $body,
                        ]);
                    } catch (TicketImportMessageProcessingException $e) {
                        $logger->error('Ошибка обработки сообщения', [
                            'message' => $e->getMessage(),
                            'body' => $body,
                        ]);
                    }
                })
                ->stopAfterFailCommit()
                ->withAutoCommit();

            $consumerTimeout = 15000;
            $messagesBatchSize = 5;
            $msg = "Подписка на топик $topicName с таймаутом $consumerTimeout мс и лимитом $messagesBatchSize сообщений.";
            $logger->info($msg);

            $consumer->consume($messagesBatchSize, $consumerTimeout);

            $timeoutSeconds = 5;
            $timeoutMsg = "Таймаут $timeoutSeconds секунд";
            $logger->info($timeoutMsg);
            sleep($timeoutSeconds);
        }

        return self::SUCCESS;
    }

    /**
     * @return void
     */
    protected function configure()
    {
        //
    }
}
