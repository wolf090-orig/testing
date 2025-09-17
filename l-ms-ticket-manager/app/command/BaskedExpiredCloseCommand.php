<?php

namespace app\command;

use app\model\Basket;
use app\model\CancelReasons;
use Carbon\Carbon;
use support\Log;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BaskedExpiredCloseCommand extends Command
{
    private const string LOG_CHANNEL = 'command_basket_close';
    protected static $defaultName = 'basked-expired:close';
    protected static $defaultDescription = 'Закрытие всех корзин с истёкшим временем жизни';

    public function run(InputInterface $input, OutputInterface $output): int
    {
        return $this->execute($input, $output);
    }

    /**
     * Закрываем все корзины с истёкшим end_date независимо от статуса лотереи
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $logger = Log::channel(self::LOG_CHANNEL);
        $logger->info('Запуск команды закрытия истекших корзин');

        $startTime = microtime(true);

        $cancelReasons = CancelReasons::where('name', CancelReasons::EXPIRED)->first();

        if (!$cancelReasons) {
            $logger->error('Не найдена причина отмены EXPIRED');
            $output->writeln('❌ Ошибка: Не найдена причина отмены EXPIRED');
            return self::FAILURE;
        }

        // Закрываем ВСЕ корзины с истёкшим временем, независимо от статуса лотереи
        $expiredBaskets = Basket::where('end_date', '<', Carbon::now())
            ->whereNull('cancel_reason_id')  // Ещё не закрытые
            ->get();

        $logger->info('Найдено истекших корзин для закрытия', [
            'count' => $expiredBaskets->count(),
            'current_time' => Carbon::now()->format('Y-m-d H:i:s')
        ]);

        $totalFound = $expiredBaskets->count();

        if ($expiredBaskets->count() === 0) {
            $logger->info('Нет истекших корзин для закрытия');
            $output->writeln('Команда завершена: Нет истекших корзин для закрытия');
            return self::SUCCESS;
        }

        $successClosed = 0;
        $errors = 0;

        foreach ($expiredBaskets as $basket) {
            try {
                $logger->info('Закрытие корзины', [
                    'basket_id' => $basket->id,
                    'user_id' => $basket->user_id,
                    'end_date' => $basket->end_date->format('Y-m-d H:i:s'),
                    'tickets_count' => $basket->tickets()->count()
                ]);

                $basket->closeBasket($cancelReasons->id);
                $successClosed++;
            } catch (\Exception $e) {
                $errors++;
                $logger->error('Ошибка при закрытии корзины', [
                    'basket_id' => $basket->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        $executionTime = round((microtime(true) - $startTime) * 1000, 2);

        $logger->info('Команда закрытия корзин завершена', [
            'success_closed' => $successClosed,
            'errors' => $errors,
            'total_processed' => $expiredBaskets->count(),
            'execution_time_ms' => $executionTime
        ]);

        $output->writeln("Команда завершена: Закрыто: {$successClosed}, ошибок: {$errors}, время: {$executionTime}ms");

        return self::SUCCESS;
    }

    /**
     * @return void
     */
    protected function configure()
    {
        $this->setName('basked-expired:close')
            ->setDescription('Закрытие всех корзин с истёкшим временем жизни')
            ->addArgument('name', InputArgument::OPTIONAL, 'Name description');
    }
}
