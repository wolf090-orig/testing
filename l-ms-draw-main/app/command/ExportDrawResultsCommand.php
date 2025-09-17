<?php

namespace app\command;

use app\classes\Interfaces\LotteryRepositoryInterface;
use app\classes\Interfaces\TicketRepositoryInterface;
use app\services\ExportService;
use Psr\Log\LoggerInterface;
use support\Container;
use support\Log;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExportDrawResultsCommand extends Command
{
    protected static $defaultName = 'export:draw-results';
    protected static $defaultDescription = 'Экспортирует результаты проведенных розыгрышей в Kafka';
    
    private ExportService $exportService;
    private LotteryRepositoryInterface $lotteryRepository;
    private TicketRepositoryInterface $ticketRepository;
    public LoggerInterface $logger;

    public function __construct()
    {
        $this->logger = Log::channel('default');
        $this->exportService = new ExportService();
        $this->lotteryRepository = Container::make(LotteryRepositoryInterface::class, []);
        $this->ticketRepository = Container::make(TicketRepositoryInterface::class, []);
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription(self::$defaultDescription);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->logger->info("Запуск команды экспорта результатов розыгрышей");
        
        // Находим лотереи которые разыграны, но результаты еще не экспортированы
        $lotteriesWithResults = $this->lotteryRepository->getDrawnLotteriesWithUnexportedResults();
        
        if (count($lotteriesWithResults) == 0) {
            $this->logger->info("Лотерей с результатами для экспорта нет, завершаем процесс");
            return Command::SUCCESS;
        }
        
        $this->logger->info("Найдено лотерей с результатами для экспорта: " . count($lotteriesWithResults), [
            'lottery_ids' => array_column($lotteriesWithResults, 'id')
        ]);
        
        $exportedCount = 0;
        
        foreach ($lotteriesWithResults as $lottery) {
            $this->logger->info("Экспортируем результаты лотереи", [
                'lottery_id' => $lottery['id'],
                'lottery_name' => $lottery['lottery_name']
            ]);
            
            try {
                // Проверяем есть ли у лотереи победители
                $winners = $this->ticketRepository->getWinnerTickets($lottery['id']);
                if (count($winners) == 0) {
                    $this->logger->info("У лотереи нет победителей, помечаем как экспортированную", [
                        'lottery_id' => $lottery['id']
                    ]);
                    // Помечаем как экспортированную даже без победителей, чтобы не проверять повторно
                    $this->lotteryRepository->markResultsAsExported($lottery['id']);
                    continue;
                }
                
                $result = $this->formatDrawResult($lottery);
                $this->exportService->publish($result);
                
                // Помечаем результаты как экспортированные
                $this->lotteryRepository->markResultsAsExported($lottery['id']);
                $exportedCount++;
                
                $this->logger->info("Результаты лотереи экспортированы", [
                    'lottery_id' => $lottery['id'],
                    'winners_count' => count($result['tickets'])
                ]);
                
            } catch (\Exception $e) {
                $this->logger->error("Ошибка экспорта результатов лотереи", [
                    'lottery_id' => $lottery['id'],
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        $this->logger->info("Команда экспорта результатов завершена", [
            'total_lotteries' => count($lotteriesWithResults),
            'exported_successfully' => $exportedCount
        ]);

        return Command::SUCCESS;
    }

    /**
     * Форматировать результат розыгрыша для экспорта
     */
    private function formatDrawResult(array $lottery): array
    {
        $winnerTickets = $this->ticketRepository->getWinnerTickets($lottery['id']);
        
        $result = [
            'lottery_id' => $lottery['id'],
            'lottery_name' => $lottery['lottery_name'],
            'draw_date' => $lottery['draw_date'],
            'tickets' => []
        ];

        foreach ($winnerTickets as $ticket) {
            $result['tickets'][] = [
                'ticket_number' => $ticket['ticket_number'],
                'winner_position' => $ticket['winner_position']
            ];
        }

        return $result;
    }

    public function run(InputInterface $input, OutputInterface $output): int
    {
        return $this->execute($input, $output);
    }
} 