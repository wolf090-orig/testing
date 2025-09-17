<?php

namespace app\command\Payments;

use app\services\PaymentCallbackService;
use support\Log;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Команда для обработки необработанных callback'ов от FPGate
 */
class ProcessCallbacksCommand extends Command
{
    private const string LOG_CHANNEL = 'command_process_callbacks';
    
    protected static string $defaultName = 'payment:process-callbacks';
    protected static string $defaultDescription = 'Обработка необработанных callback\'ов от FPGate';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $logger = Log::channel(self::LOG_CHANNEL);
        $batchSize = (int) ($_ENV['PAYMENT_CALLBACK_BATCH_SIZE'] ?? 100);
        
        $logger->info('Запуск команды обработки callback\'ов', [
            'batch_size' => $batchSize
        ]);

        try {
            // Создаем сервис - зависимости получаются из контейнера
            $paymentCallbackService = new PaymentCallbackService();
            
            // Делегируем всю логику сервису
            $result = $paymentCallbackService->processUnprocessedCallbacks($batchSize);
            
            $logger->info('Команда обработки callback\'ов завершена', [
                'processed' => $result['processed'],
                'errors' => $result['errors']
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $logger->error('Ошибка выполнения команды обработки callback\'ов', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Command::FAILURE;
        }
    }
}
