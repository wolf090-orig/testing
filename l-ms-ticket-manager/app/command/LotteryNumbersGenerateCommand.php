<?php

namespace app\command;

use app\model\Country;
use app\services\LotteryGeneratorService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use support\Log;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class LotteryNumbersGenerateCommand extends Command
{
    protected const string LOG_CHANNEL = 'command_lottery_numbers_generator';

    protected static string $defaultName = 'lottery-numbers:generate';
    protected static string $defaultDescription = 'Генерация лотерей для указанного периода и стран';

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $logger = Log::channel(self::LOG_CHANNEL);
        $logger->info('Запуск команды генерации лотерей');

        $startTime = microtime(true);

        $period = null;

        // Приоритет: конкретные даты > количество дней > дефолт (3 дня)
        if ($input->hasArgument('start') && $input->getArgument('start')) {
            $start = Carbon::parse($input->getArgument('start'));
            $end = Carbon::parse($input->getArgument('end'));
            $period = CarbonPeriod::create($start, $end);
            $logger->info('Установлен период по датам', [
                'start' => $start->format('Y-m-d'),
                'end' => $end->format('Y-m-d')
            ]);
        } elseif ($input->hasOption('days') && $input->getOption('days')) {
            $days = (int)$input->getOption('days');
            if ($days < 1 || $days > 365) {
                $logger->error('Неверное количество дней', ['days' => $days]);
                $output->writeln("Команда завершена: Ошибка: Неверное количество дней ({$days}). Допустимо: 1-365");
                return self::FAILURE;
            }
            $startDate = Carbon::now();
            $endDate = Carbon::now()->addDays($days - 1);
            $period = CarbonPeriod::create($startDate, $endDate);
            $logger->info('Установлен период по количеству дней', [
                'days' => $days,
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d')
            ]);
        } else {
            $logger->info('Используется период по умолчанию: 3 дня');
        }

        $countries = null;
        if ($input->hasOption('countries') && $input->getOption('countries')) {
            $countries = explode(',', $input->getOption('countries'));
            // Валидируем коды стран
            $validCountries = Country::getActiveCodes();
            $countries = array_filter($countries, fn($code) => in_array(strtoupper($code), $validCountries));

            if (empty($countries)) {
                $logger->error('Не указаны валидные коды стран', [
                    'requested' => explode(',', $input->getOption('countries')),
                    'available' => $validCountries
                ]);
                $output->writeln("Команда завершена: Ошибка: Не указаны валидные коды стран");
                return self::FAILURE;
            }
            $logger->info('Установлены конкретные страны', ['countries' => $countries]);
        } else {
            $activeCountries = Country::getActiveCodes();
            $logger->info('Используются все активные страны', ['countries' => $activeCountries]);
        }

        // Логируем начало процесса генерации
        $logger->info('Начинаем генерацию лотерей', [
            'period_info' => $period ?
                "Период: {$period->start->format('d.m.Y')} - {$period->end->format('d.m.Y')}" :
                "Период: ближайшие 3 дня (по умолчанию)",
            'countries_info' => $countries ?
                "Страны: " . implode(', ', $countries) :
                "Страны: все активные (" . implode(', ', Country::getActiveCodes()) . ")"
        ]);

        try {
            $generator = new LotteryGeneratorService();
            $result = $generator->generateLotteryNumbers($period, $countries);

            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            $logger->info('Команда генерации лотерей завершена успешно', [
                'execution_time_ms' => $executionTime
            ]);

            // Подсчитываем результат (предполагаем что сервис может вернуть статистику)
            $countriesCount = $countries ? count($countries) : count(Country::getActiveCodes());
            $daysCount = $period ? $period->count() : 3;

            $output->writeln("Команда завершена: Сгенерировано лотерей для {$countriesCount} стран на {$daysCount} дней, время: {$executionTime}ms");
        } catch (\Exception $e) {
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            $logger->error('Ошибка при генерации лотерей', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'execution_time_ms' => $executionTime
            ]);

            $output->writeln("Команда завершена: Ошибка при генерации лотерей: {$e->getMessage()}, время: {$executionTime}ms");
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('lottery-numbers:generate')
            ->setDescription('Генерация лотерей для указанного периода и стран')
            ->addArgument('start', InputArgument::OPTIONAL, 'Дата начала периода: 2024-12-30')
            ->addArgument('end', InputArgument::OPTIONAL, 'Дата окончания периода: 2024-12-30')
            ->addOption('countries', 'c', InputOption::VALUE_OPTIONAL, 'Коды стран через запятую: RU,UZ,BY')
            ->addOption('days', 'd', InputOption::VALUE_OPTIONAL, 'Количество дней для генерации (1-365): 7');
    }
}
