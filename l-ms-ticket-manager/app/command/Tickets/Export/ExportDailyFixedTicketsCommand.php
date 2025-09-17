<?php

namespace app\command\Tickets\Export;

use app\enums\LotteryTypeEnum;
use app\services\TicketExportService;
use Psr\Log\LoggerInterface;
use support\Log;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExportDailyFixedTicketsCommand extends Command
{
    protected static $defaultName = 'export_tickets:daily_fixed';
    protected static $defaultDescription = 'Экспорт билетов ежедневной лотереи с фиксированным призом';

    private TicketExportService $ticketExportService;
    private LoggerInterface $logger;

    public function __construct()
    {
        parent::__construct();
        $this->logger = Log::channel('command_export_daily_fixed_tickets');
        $this->ticketExportService = new TicketExportService('kafka.daily_fixed_tickets_topic', $this->logger);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $startTime = microtime(true);

        $chunkSize = env('USER_TICKETS_EXPORT_CHUNK_SIZE', 1000);
        $maxTickets = env('USER_TICKETS_EXPORT_MAX_TICKETS', 100000);
        $lotteryId = $input->getOption('lottery-id');

        if (!$lotteryId) {
            $this->logger->info('Получение всех активных ежедневных лотерей с фиксированным призом');
            $lotteryIds = $this->ticketExportService->getActiveLotteryIdsByType(LotteryTypeEnum::DAILY_FIXED);
            if (empty($lotteryIds)) {
                $this->logger->info('Не найдено активных лотерей типа "' . LotteryTypeEnum::DAILY_FIXED . '"');
                $executionTime = round((microtime(true) - $startTime) * 1000, 2);
                $output->writeln("Команда завершена: Нет активных ежедневных лотерей с фиксированным призом, время: {$executionTime}ms");
                return Command::SUCCESS;
            }
        } else {
            $lotteryIds = [$lotteryId];
        }

        $totalExported = 0;
        $errors = [];

        foreach ($lotteryIds as $currentLotteryId) {
            $this->logger->info('Вызов сервиса для экспорта тикетов', ['lottery_id' => $currentLotteryId]);

            try {
                $this->ticketExportService->exportTickets($currentLotteryId, $chunkSize, $maxTickets);
                $totalExported++;

                $this->logger->info('Экспорт билетов завершён для лотереи', [
                    'lottery_id' => $currentLotteryId
                ]);

            } catch (\Exception $e) {
                $errors[] = "Лотерея {$currentLotteryId}: {$e->getMessage()}";
                
                $this->logger->error('Ошибка при экспорте билетов', [
                    'lottery_id' => $currentLotteryId,
                    'error' => $e->getMessage()
                ]);
            }
        }

        $executionTime = round((microtime(true) - $startTime) * 1000, 2);

        if (!empty($errors)) {
            $this->logger->error('Экспорт завершён с ошибками', [
                'total_lotteries' => count($lotteryIds),
                'exported_successfully' => $totalExported,
                'errors' => $errors,
                'execution_time_ms' => $executionTime
            ]);

            $output->writeln("Команда завершена: Экспорт daily_fixed - обработано {$totalExported} из " . count($lotteryIds) . " лотерей, время: {$executionTime}ms");
            $output->writeln("Ошибки: " . implode('; ', $errors));
            return Command::FAILURE;
        }

        $this->logger->info('Экспорт билетов завершён успешно', [
            'total_lotteries' => count($lotteryIds),
            'execution_time_ms' => $executionTime
        ]);

        $output->writeln("Команда завершена: Экспорт билетов daily_fixed для {$totalExported} лотерей завершён, время: {$executionTime}ms");

        return Command::SUCCESS;
    }

    protected function configure(): void
    {
        $this->setName(self::$defaultName);
        $this->addOption('lottery-id', null, InputOption::VALUE_REQUIRED, 'ID лотереи для экспорта билетов');
    }
}
