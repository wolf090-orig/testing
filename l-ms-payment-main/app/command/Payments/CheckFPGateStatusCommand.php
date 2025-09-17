<?php

namespace app\command\Payments;

use app\services\PaymentStatusCheckService;
use support\Log;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Команда для опроса статусов незавершенных платежей в FPGate
 */
class CheckFPGateStatusCommand extends Command
{
    private const string LOG_CHANNEL = 'command_check_fpgate_status';
    
    protected static string $defaultName = 'payment:check-fpgate-status';
    protected static string $defaultDescription = 'Проверка статусов платежей в FPGate';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $logger = Log::channel(self::LOG_CHANNEL);
        $batchSize = (int) ($_ENV['FPGATE_STATUS_CHECK_BATCH_SIZE'] ?? 1);
        
        $logger->info('Запуск команды проверки статусов FPGate', [
            'batch_size' => $batchSize
        ]);

        try {
            // Создаем сервис как в тикет менеджере
            $paymentStatusCheckService = new PaymentStatusCheckService();
            
            // Делегируем всю логику сервису
            $result = $paymentStatusCheckService->checkPendingTransactions($batchSize);
            
            $logger->info('Команда проверки статусов FPGate завершена', [
                'checked' => $result['checked'],
                'updated' => $result['updated'],
                'errors' => $result['errors']
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $logger->error('Ошибка выполнения команды проверки статусов FPGate', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Command::FAILURE;
        }
    }
}
