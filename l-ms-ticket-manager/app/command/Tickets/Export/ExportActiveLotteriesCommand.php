<?php

namespace app\command\Tickets\Export;

use app\libraries\kafka\messages\KafkaProducerMessage;
use app\libraries\kafka\producers\Producer;
use app\services\TicketService;
use Exception;
use Psr\Log\LoggerInterface;
use support\Log;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExportActiveLotteriesCommand extends Command
{
    protected static string $defaultName = 'export_active_lotteries:run';
    protected static string $defaultDescription = 'Отправить активные лотереи в Kafka';

    private TicketService $ticketService;
    private Producer $producer;
    private LoggerInterface $logger;

    public function __construct()
    {
        $this->ticketService = new TicketService();
        $this->producer = Producer::createFromConfigKey(
            'tickets',
            config('kafka.lottery_schedules_topic')
        );
        $this->logger = Log::channel('command_export_active_lotteries');

        parent::__construct();
    }

    public function run(InputInterface $input, OutputInterface $output): int
    {
        return $this->execute($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $startTime = microtime(true);

        $activeLotteries = $this->ticketService->getActiveLotteries();

        if (empty($activeLotteries)) {
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            $this->logger->info('Нет активных лотерей для отправки');
            $output->writeln("Команда завершена: Нет активных лотерей, время: {$executionTime}ms");
            return Command::SUCCESS;
        }

        $body = [
            'lotteries' => $activeLotteries,
        ];

        $this->logger->info('Получены активные лотереи', ['count' => count($activeLotteries)]);

        try {
            $message = new KafkaProducerMessage($body);
            $this->producer->sendMessage($message);

            // Помечаем лотереи как отправленные
            $lotteryIds = array_column($activeLotteries, 'id');
            $this->ticketService->markLotteriesScheduleExported($lotteryIds);

            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            $this->logger->info('Активные лотереи отправлены в kafka', [
                'count' => count($activeLotteries),
                'lottery_ids' => $lotteryIds,
                'execution_time_ms' => $executionTime
            ]);

            $output->writeln("Команда завершена: Отправлено " . count($activeLotteries) . " активных лотерей в Kafka, время: {$executionTime}ms");
        } catch (Exception $e) {
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            $this->logger->error('Произошла ошибка при отправке сообщения в Kafka: ' . $e->getMessage(), [
                'execution_time_ms' => $executionTime
            ]);

            $output->writeln("Команда завершена: Ошибка отправки в Kafka: {$e->getMessage()}, время: {$executionTime}ms");
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    protected function configure(): void
    {
        $this->setDescription(self::$defaultDescription);
    }
}
