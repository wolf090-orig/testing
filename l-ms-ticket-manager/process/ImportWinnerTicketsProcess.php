<?php

namespace process;

use app\command\Tickets\Import\ImportWinnerTicketsCommand;
use Closure;
use support\Log;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Workerman\Worker;

/**
 * Процесс автоматического импорта выигрышных билетов из Kafka
 * Запускается как отдельный worker для непрерывного чтения сообщений
 */
class ImportWinnerTicketsProcess
{
    private const string PROCESS_LOG_CHANNEL = 'process_import_winner_tickets';

    public function onWorkerStart(): void
    {
        $logger = Log::channel(self::PROCESS_LOG_CHANNEL);
        $logger->info('Процесс импорта выигрышных билетов инициализирован', [
            'description' => 'Непрерывное чтение сообщений из Kafka топика lottery_draw_results_v1'
        ]);

        // Запускаем команду импорта в отдельном потоке
        $this->runImportWinnerTicketsCommand();
    }

    /**
     * Запускает команду импорта выигрышных билетов
     */
    private function runImportWinnerTicketsCommand(): void
    {
        $logger = Log::channel(self::PROCESS_LOG_CHANNEL);
        
        try {
            $logger->info('🚀 Запуск команды импорта выигрышных билетов');
            
            $command = new ImportWinnerTicketsCommand();
            $input = new ArrayInput([]);
            $output = new BufferedOutput();
            
            // Команда запускается в бесконечном цикле
            $command->run($input, $output);
            
        } catch (\Throwable $e) {
            $logger->error('❌ Критическая ошибка в процессе импорта выигрышных билетов', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Перезапускаем через 30 секунд
            sleep(30);
            $this->runImportWinnerTicketsCommand();
        }
    }
} 