<?php

namespace app\command;

use app\services\DrawService;
use app\services\ExportService;
use Psr\Log\LoggerInterface;
use support\Log;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DrawLotteriesCommand extends Command
{
    protected static $defaultName = 'draw:lotteries';
    protected static $defaultDescription = 'Проводит розыгрыш лотерей готовых к розыгрышу';
    private ExportService $exportService;
    public LoggerInterface $logger;

    public function __construct()
    {
        $this->logger = Log::channel('default');
        $this->exportService = new ExportService();
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription(self::$defaultDescription);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->logger->info("Запуск команды розыгрыша лотерей");
        
        $lotteries = $this->exportService->getLotteries2Draw();
        if (count($lotteries) == 0) {
            $this->logger->info("Лотерей готовых к розыгрышу нет, завершаем процесс");
            return Command::SUCCESS;
        }
        
        $this->logger->info("Найдено лотерей для розыгрыша: " . count($lotteries), [
            'lottery_ids' => array_column($lotteries, 'id')
        ]);
        
        $drawService = new DrawService();
        $drawnCount = 0;
        
        foreach ($lotteries as $lottery) {
            $this->logger->info("Проводим розыгрыш лотереи", [
                'lottery_id' => $lottery['id'],
                'lottery_name' => $lottery['lottery_name']
            ]);
            
            $success = $drawService->drawLottery($lottery['id']);
            if ($success) {
                $drawnCount++;
                $this->logger->info("Розыгрыш лотереи завершен успешно", [
                    'lottery_id' => $lottery['id']
                ]);
            } else {
                $this->logger->warning("Розыгрыш лотереи завершен с ошибкой", [
                    'lottery_id' => $lottery['id']
                ]);
            }
        }
        
        $this->logger->info("Команда розыгрыша завершена", [
            'total_lotteries' => count($lotteries),
            'drawn_with_winners' => $drawnCount
        ]);

        return Command::SUCCESS;
    }

    public function run(InputInterface $input, OutputInterface $output): int
    {
        return $this->execute($input, $output);
    }
}
