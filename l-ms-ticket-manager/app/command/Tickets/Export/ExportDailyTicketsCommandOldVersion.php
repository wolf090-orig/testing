<?php

namespace app\command\Tickets\Export;

use app\libraries\kafka\messages\KafkaProducerMessage;
use app\libraries\kafka\producers\Producer;
use app\services\TicketService;
use Exception;
use support\Db;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExportDailyTicketsCommandV2 extends Command
{
    protected static $defaultName = 'export_tickets:daily_old_version';
    protected static $defaultDescription = 'Создать билеты для указанного lottery_id или ежедневных лотерей';

    private Producer $producer;
    private TicketService $ticketService;
    private OutputInterface $output;

    public function __construct()
    {
        parent::__construct();
        $this->producer = Producer::createFromConfigKey('tickets', config('kafka.daily_tickets_topic'));
        $this->ticketService = new TicketService();
    }

    protected function configure(): void
    {
        $this->addArgument('lottery_id', InputArgument::OPTIONAL, 'ID лотереи');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;
        $chunkSize = env('USER_TICKETS_EXPORT_CHUNK_SIZE', 1000);
        $maxTickets = env('USER_TICKETS_EXPORT_MAX_TICKETS', 100000);
        $lotteryId = $input->getArgument('lottery_id');

        $this->output->writeln('Начинаем процесс экспорта билетов');
        if ($lotteryId) {
            $this->output->writeln("Проверка, является ли лотерея с ID: $lotteryId ежедневной");
            if (!$this->ticketService->isLotteryOfType($lotteryId, 'daily')) {
                $this->output->writeln("Лотерея с ID: $lotteryId не является ежедневной");
                return self::SUCCESS;
            }
        } else {
            $this->output->writeln('Получение активной ежедневной лотереи');
            $lotteryId = $this->ticketService->getActiveLotteryIdByType('daily');
            if (!$lotteryId) {
                $this->output->writeln('Не найдено активных лотерей типа "daily"');
                return self::SUCCESS;
            }
        }

        $this->output->writeln("Получение билетов для лотереи с ID: $lotteryId");
        $tickets = $this->ticketService->getUserTicketsForExport($lotteryId, $maxTickets);
        $this->output->writeln("Получено билетов: " . count($tickets));

        if (empty($tickets)) {
            $this->output->writeln("Не найдено билетов для лотереи с ID: $lotteryId");
            $this->output->writeln("Команда завершена");
            return self::SUCCESS;
        }

        $this->output->writeln('Начинаем обработку билетов по чанкам');
        $this->processTicketsInChunks($tickets, $chunkSize, $lotteryId);

        $this->output->writeln("Команда завершена");
        return self::SUCCESS;
    }

    private function processTicketsInChunks(array $tickets, int $chunkSize, int $lotteryId): void
    {
        $chunks = array_chunk($tickets, $chunkSize);
        foreach ($chunks as $chunkIndex => $chunk) {
            $ticketNumbers = array_column($chunk, 'ticket_number_id');
            $ticketIds = array_column($chunk, 'id');

            $this->output->writeln("Обработка чанка $chunkIndex, количество билетов в чанке: "
                . count($ticketNumbers));

            if (!empty($ticketNumbers)) {
                $this->processChunk($chunkIndex, $ticketNumbers, $ticketIds, $lotteryId);
            }
        }
    }

    private function processChunk(int $chunkIndex, array $ticketNumbers, array $ticketIds, int $lotteryId): void
    {
        $messageData = [
            'lottery_id' => $lotteryId,
            'tickets' => $ticketNumbers
        ];

        $kafkaMessage = new KafkaProducerMessage($messageData);
        $kafkaMessage->withHeaders(['lottery_id' => $lotteryId]);

        $this->output->writeln("Начинаем транзакцию для батча $chunkIndex");
        Db::beginTransaction();

        try {
            $this->output->writeln("Отправка сообщения в Kafka для лотереи с ID: $lotteryId");
            $this->producer->sendMessage($kafkaMessage);

            $this->output->writeln("Сообщение отправлено в Kafka. Обновление даты экспорта билетов");
            $this->ticketService->markTicketsExported($ticketIds);

            Db::commit();
            $this->output->writeln("Статус билетов обновлен для батча $chunkIndex, лотереи с ID: $lotteryId");
        } catch (Exception $e) {
            Db::rollBack();
            $this->output->writeln(
                "Ошибка обработки батча $chunkIndex для лотереи с ID: $lotteryId. Ошибка: " . $e->getMessage()
            );
        }
    }
}

