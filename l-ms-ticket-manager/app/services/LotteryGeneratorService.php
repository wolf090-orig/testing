<?php

namespace app\services;

use app\model\Country;
use app\model\LotteryNumber;
use app\model\LotteryTypes;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Exception;
use PDO;
use support\Log;
use support\Db;

class LotteryGeneratorService
{
    /**
     * Константа, определяющая количество билетов в лотерее.
     */
    public const int LOTTERY_TICKETS_COUNT = 1000;

    /**
     * Страна по умолчанию для генерации лотерей
     */
    public const string DEFAULT_COUNTRY = 'RU';

    /**
     * Канал логирования для генерации лотерей
     */
    public const string LOG_CHANNEL = 'command_lottery_numbers_generator';

    /**
     * Генерирует лотереи за указанный период на основе активных расписаний.
     *
     * @param CarbonPeriod|null $period
     * @param array|null $countries - массив кодов стран для генерации (по умолчанию все активные)
     * @return void
     */
    public function generateLotteryNumbers(?CarbonPeriod $period = null, ?array $countries = null): void
    {
        $logger = Log::channel(self::LOG_CHANNEL);

        if (empty($period)) {
            // По умолчанию генерируем лотереи на ближайшие 3 дня
            $startDate = Carbon::now();
            $endDate = Carbon::now()->addDays(2); // 3 дня включая сегодня
            $period = CarbonPeriod::create($startDate, $endDate);
        }

        if (empty($countries)) {
            // Получаем активные страны из БД
            $countries = Country::getActiveCodes();
        }

        $logger->info('Начинаем генерацию лотерей', [
            'period_start' => $period->start->format('Y-m-d'),
            'period_end' => $period->end->format('Y-m-d'),
            'countries' => $countries
        ]);

        // Получаем типы лотерей с их настройками
        $lotteryTypes = collect(Db::table('lottery_types')->get())->keyBy('id');

        // Получаем активные расписания
        $schedules = Db::table('lottery_types_schedules')
            ->where('is_active', true)
            ->get();

        $totalCreated = 0;

        foreach ($countries as $countryCode) {
            $logger->info("Генерируем лотереи для страны: {$countryCode}");

            // Получаем ID страны по коду
            $country = Country::findByCode($countryCode);
            if (!$country) {
                $logger->warning("Страна с кодом {$countryCode} не найдена");
                continue;
            }

            $countryCreated = 0;

            foreach ($period as $day) {
                $day = Carbon::parse($day);

                foreach ($schedules as $schedule) {
                    if (!isset($lotteryTypes[$schedule->type_id])) {
                        continue;
                    }

                    $lotteryType = $lotteryTypes[$schedule->type_id];

                    // Проверяем, нужно ли создавать лотерею этого типа для данной страны
                    if (!$this->shouldCreateLottery($lotteryType, $schedule, $day, $country, $logger)) {
                        continue;
                    }

                    $this->createLottery($lotteryType, $schedule, $day, $country, $logger);
                    $countryCreated++;
                    $totalCreated++;
                }
            }

            $logger->info("Создано лотерей для страны {$countryCode}: {$countryCreated}");
        }

        $logger->info('Генерация лотерей завершена', [
            'period_start' => $period->start->format('Y-m-d'),
            'period_end' => $period->end->format('Y-m-d'),
            'total_created' => $totalCreated,
            'countries' => $countries
        ]);
    }

    /**
     * Проверяет, нужно ли создавать лотерею данного типа.
     *
     * @param object $lotteryType
     * @param object $schedule
     * @param Carbon $day
     * @param Country $country
     * @return bool
     */
    private function shouldCreateLottery($lotteryType, $schedule, Carbon $day, Country $country, $logger): bool
    {
        // Для длительных лотерей (jackpot, supertour) проверяем, есть ли незавершенные для данной страны
        if (in_array($lotteryType->name, ['jackpot', 'supertour'])) {
            $hasUnfinished = Db::table('lottery_numbers')
                ->where('lottery_type_id', $lotteryType->id)
                ->where('country_id', $country->id)
                ->where('is_drawn', false)
                ->exists();

            if ($hasUnfinished) {
                $logger->info("Пропускаем создание {$lotteryType->name} для {$country->code} - есть незавершенная лотерея");
                return false;
            }
        }

        // Генерируем имя лотереи
        $lotteryName = $this->generateLotteryName($lotteryType, $schedule, $day, $country->code);

        // Проверяем, существует ли уже лотерея с таким именем для данной страны
        $exists = Db::table('lottery_numbers')
            ->where('lottery_name', $lotteryName)
            ->where('country_id', $country->id)
            ->exists();

        return !$exists;
    }

    /**
     * Создает лотерею.
     *
     * @param object $lotteryType
     * @param object $schedule
     * @param Carbon $day
     * @param Country $country
     * @return void
     */
    private function createLottery($lotteryType, $schedule, Carbon $day, Country $country, $logger): void
    {
        $lotteryName = $this->generateLotteryName($lotteryType, $schedule, $day, $country->code);

        // Рассчитываем даты
        $startDate = $day->copy()->setTimeFromTimeString($schedule->sale_start_time);
        $endDate = $this->calculateEndDate($lotteryType, $day, $schedule);
        $drawDate = $this->calculateDrawDate($lotteryType, $day, $schedule);

        // Получаем конфигурацию призов по умолчанию
        $prizeConfigId = Db::table('prize_configurations')
            ->where('lottery_type_id', $lotteryType->id)
            ->where('is_active', true)
            ->value('id');

        $lotteryData = [
            'lottery_name' => $lotteryName,
            'start_date' => $startDate->format('Y-m-d H:i:s'),
            'end_date' => $endDate->format('Y-m-d H:i:s'),
            'draw_date' => $drawDate->format('Y-m-d H:i:s'),
            'lottery_type_id' => $lotteryType->id,
            'country_id' => $country->id,
            'is_drawn' => false,
            'is_active' => true,
            'is_prize_config_locked' => false,
            'prize_configuration_id' => $prizeConfigId,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ];

        Db::table('lottery_numbers')->insert($lotteryData);

        $logger->info("Создана лотерея: {$lotteryName} для страны {$country->code}");
    }

    /**
     * Генерирует имя лотереи с учетом типа, расписания, даты и страны.
     *
     * @param object $lotteryType
     * @param object $schedule
     * @param Carbon $day
     * @param string $countryCode
     * @return string
     */
    private function generateLotteryName($lotteryType, $schedule, Carbon $day, string $countryCode): string
    {
        $datePart = $day->format('d.m.Y');
        $countryPrefix = strtoupper($countryCode);

        switch ($lotteryType->name) {
            case 'daily_fixed':
                return "{$countryPrefix} Daily Fixed {$datePart}";

            case 'daily_dynamic':
                return "{$countryPrefix} Daily Dynamic {$schedule->schedule_name} {$datePart}";

            case 'jackpot':
                return "{$countryPrefix} Jackpot {$datePart}";

            case 'supertour':
                return "{$countryPrefix} Supertour {$datePart}";

            default:
                return "{$countryPrefix} " . ucfirst($lotteryType->name) . " {$datePart}";
        }
    }

    /**
     * Рассчитывает дату окончания продажи билетов.
     *
     * @param object $lotteryType
     * @param Carbon $day
     * @param object $schedule
     * @return Carbon
     */
    private function calculateEndDate($lotteryType, Carbon $day, $schedule): Carbon
    {
        // Для супертура - продажа заканчивается после продажи всех билетов
        // Устанавливаем максимальную дату, фактическое окончание будет контролироваться логикой продаж
        if ($lotteryType->name === 'supertour') {
            return $day->copy()
                ->addDays($lotteryType->duration_days ?? 30)
                ->setTimeFromTimeString($schedule->sale_end_time);
        }

        // Для джекпота и других длительных лотерей - добавляем дни к дате начала
        if (in_array($lotteryType->name, ['jackpot']) && $lotteryType->duration_days) {
            return $day->copy()
                ->addDays($lotteryType->duration_days - 1)
                ->setTimeFromTimeString($schedule->sale_end_time);
        }

        // Для дневных лотерей - в тот же день
        return $day->copy()->setTimeFromTimeString($schedule->sale_end_time);
    }

    /**
     * Рассчитывает дату проведения розыгрыша.
     *
     * @param object $lotteryType
     * @param Carbon $day
     * @param object $schedule
     * @return Carbon
     */
    private function calculateDrawDate($lotteryType, Carbon $day, $schedule): Carbon
    {
        // Для супертура - розыгрыш после продажи всех билетов
        // Дата розыгрыша будет обновляться динамически при достижении лимита билетов
        if ($lotteryType->name === 'supertour') {
            return $day->copy()
                ->addDays($lotteryType->duration_days ?? 30)
                ->setTimeFromTimeString($schedule->draw_time);
        }

        // Для джекпота и других длительных лотерей - розыгрыш в последний день
        if (in_array($lotteryType->name, ['jackpot']) && $lotteryType->duration_days) {
            return $day->copy()
                ->addDays($lotteryType->duration_days - 1)
                ->setTimeFromTimeString($schedule->draw_time);
        }

        // Для дневных лотерей - в тот же день
        return $day->copy()->setTimeFromTimeString($schedule->draw_time);
    }

    /**
     * Проверяет достигнут ли лимит билетов для лотереи (актуально для супертура).
     *
     * @param int $lotteryId
     * @return bool
     */
    public function isTicketLimitReached(int $lotteryId): bool
    {
        $lottery = Db::table('lottery_numbers as ln')
            ->join('lottery_types as lt', 'ln.lottery_type_id', '=', 'lt.id')
            ->where('ln.id', $lotteryId)
            ->select('lt.name', 'lt.max_tickets_count')
            ->first();

        if (!$lottery || !$lottery->max_tickets_count) {
            return false;
        }

        // Подсчитываем количество проданных билетов
        $soldTickets = Db::table('user_ticket')
            ->where('lottery_id', $lotteryId)
            ->count();

        return $soldTickets >= $lottery->max_tickets_count;
    }

    /**
     * Получает информацию о прогрессе продаж для лотереи с ограниченным количеством билетов.
     *
     * @param int $lotteryId
     * @return array|null
     */
    public function getTicketSalesProgress(int $lotteryId): ?array
    {
        $lottery = Db::table('lottery_numbers as ln')
            ->join('lottery_types as lt', 'ln.lottery_type_id', '=', 'lt.id')
            ->where('ln.id', $lotteryId)
            ->select('ln.lottery_name', 'lt.name as type_name', 'lt.max_tickets_count')
            ->first();

        if (!$lottery || !$lottery->max_tickets_count) {
            return null;
        }

        $soldTickets = Db::table('user_ticket')
            ->where('lottery_id', $lotteryId)
            ->count();

        $remainingTickets = max(0, $lottery->max_tickets_count - $soldTickets);
        $progressPercent = round(($soldTickets / $lottery->max_tickets_count) * 100, 2);

        return [
            'lottery_name' => $lottery->lottery_name,
            'type_name' => $lottery->type_name,
            'max_tickets' => $lottery->max_tickets_count,
            'sold_tickets' => $soldTickets,
            'remaining_tickets' => $remainingTickets,
            'progress_percent' => $progressPercent,
            'is_limit_reached' => $remainingTickets === 0
        ];
    }

    /**
     * Настраивает лотерею.
     *
     * @param LotteryNumber $lotteryNumber
     * @return void
     * @throws Exception
     */
    public function setUpLottery(LotteryNumber $lotteryNumber): void
    {
        $pdo = $this->getCustomPDOConnection();
        list($parentTable, $partitionName) = $lotteryNumber->getTablePartitionName();

        // Создаем партицию для билетов лотереи
        $this->createPartition($pdo, $partitionName, $parentTable, $lotteryNumber->id);
        $this->fillLotteryPartition($pdo, $lotteryNumber, $partitionName);

        // Создаем партицию для покупок билетов этой лотереи
        $this->createUserPurchasesPartition($pdo, $lotteryNumber->id);

        // Создаем партицию для выигрышных билетов этой лотереи
        $this->createWinnerTicketsPartition($pdo, $lotteryNumber->id);
    }

    /**
     * Получает пользовательское соединение PDO.
     *
     * @return PDO
     */
    private function getCustomPDOConnection(): PDO
    {
        $config = config('database.connections.pgsql');
        $conStr = sprintf(
            "pgsql:host=%s;port=%d;dbname=%s;user=%s;password=%s",
            $config['host'],
            $config['port'],
            $config['database'],
            $config['username'],
            $config['password']
        );

        $pdo = new PDO($conStr);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $pdo;
    }

    /**
     * Создает партицию таблицы напрямую через PARTITION OF для LIST партиционирования.
     *
     * @param PDO $pdo
     * @param string $tableName
     * @param string $parentTable
     * @param int $lotteryId
     * @return void
     */
    private function createPartition(PDO $pdo, string $tableName, string $parentTable, int $lotteryId): void
    {
        if (!$this->checkTable($pdo, $tableName)) {
            // Создаем партицию напрямую
            $query = "
                CREATE TABLE {$tableName} PARTITION OF {$parentTable}
                FOR VALUES IN ({$lotteryId})
            ";
            $pdo->exec($query);
        }
    }

    /**
     * Проверяет существование таблицы.
     *
     * @param PDO $pdo
     * @param string $table
     * @return bool
     */
    private function checkTable(PDO $pdo, $table): bool
    {
        $config = config('database.connections.pgsql');
        $sql = "SELECT EXISTS(
            SELECT *
            FROM information_schema.tables
            WHERE
              table_name = '{$table}'
        )";
        $prepare = $pdo->query($sql);
        return $prepare->fetchObject()->exists ?? false;
    }

    /**
     * Заполняет партицию лотерейными номерами
     *
     * @throws Exception
     */
    private function fillLotteryPartition(PDO $pdo, LotteryNumber $lotteryNumber, string $partitionName): void
    {
        // Для лотерей с лимитом билетов - проверяем что все билеты созданы
        if (!is_null($lotteryNumber->type->max_tickets_count)) {
            $this->generateLimitedTickets($pdo, $lotteryNumber, $partitionName);
            return;
        }

        // Для безлимитных лотерей - используем автодогенерацию
        $this->generateUnlimitedTickets($pdo, $lotteryNumber, $partitionName);
    }

    /**
     * Генерирует все билеты для лотерей с лимитом
     */
    private function generateLimitedTickets(PDO $pdo, LotteryNumber $lotteryNumber, string $partitionName): void
    {
        $maxTickets = $lotteryNumber->type->max_tickets_count;
        $currentCount = $pdo->query("SELECT COUNT(*) FROM {$partitionName}")->fetchColumn();

        // Если билеты уже созданы - выходим
        if ($currentCount >= $maxTickets) {
            return;
        }

        // Очищаем и создаем все билеты сразу
        if ($currentCount > 0) {
            $pdo->exec("TRUNCATE {$partitionName}");
        }

        $this->generateTicketsBatch($pdo, $lotteryNumber, $partitionName, 1, $maxTickets);
    }

    /**
     * Генерирует билеты для безлимитных лотерей (изначально 1000, потом по 1000 при необходимости)
     */
    private function generateUnlimitedTickets(PDO $pdo, LotteryNumber $lotteryNumber, string $partitionName): void
    {
        $currentCount = $pdo->query("SELECT COUNT(*) FROM {$partitionName}")->fetchColumn();

        // Если билетов нет - создаем первую партию
        if ($currentCount == 0) {
            $this->generateTicketsBatch($pdo, $lotteryNumber, $partitionName, 1, self::LOTTERY_TICKETS_COUNT);
            return;
        }

        // Проверяем сколько билетов осталось в продаже
        $soldTickets = Db::table('user_ticket_purchases')
            ->where('lottery_id', $lotteryNumber->id)
            ->count();

        $availableTickets = $currentCount - $soldTickets;

        // Если осталось 100 или меньше - добавляем еще 1000
        if ($availableTickets <= 100) {
            $nextStartId = $currentCount + 1;
            $this->generateTicketsBatch($pdo, $lotteryNumber, $partitionName, $nextStartId, self::LOTTERY_TICKETS_COUNT);
        }
    }

    /**
     * Генерирует партию билетов
     */
    private function generateTicketsBatch(PDO $pdo, LotteryNumber $lotteryNumber, string $partitionName, int $startId, int $count): void
    {
        $batchSize = 20000;
        $insertedTickets = 0;
        $startTime = microtime(true);

        for ($offset = 0; $offset < $count; $offset += $batchSize) {
            $pdo->beginTransaction();

            try {
                $currentBatchSize = min($batchSize, $count - $insertedTickets);
                $tickets = $lotteryNumber->generateCountrySpecificTicketNumbers($startId + $insertedTickets, $currentBatchSize);

                $this->batchInsertTickets($pdo, $partitionName, $tickets);

                $insertedTickets += $currentBatchSize;
                $this->displayProgress($insertedTickets, $count, $startTime);

                $pdo->commit();
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
        }
    }

    private function batchInsertTickets(PDO $pdo, string $tableName, array $tickets): void
    {
        $placeholders = implode(', ', array_fill(0, count($tickets), '(?, ?, ?, ?)'));
        $query = "INSERT INTO {$tableName} (id, ticket_number, lottery_id, lottery_type_id) VALUES {$placeholders}";

        $stmt = $pdo->prepare($query);

        $values = [];
        foreach ($tickets as $ticket) {
            $values[] = $ticket['id'];
            $values[] = $ticket['ticket_number'];
            $values[] = $ticket['lottery_id'];
            $values[] = $ticket['lottery_type_id'];
        }

        $stmt->execute($values);
    }

    private function displayProgress(int $insertedTickets, int $totalTickets, float $startTime): void
    {
        $remainingTickets = $totalTickets - $insertedTickets;
        $elapsedTime = round(microtime(true) - $startTime, 2);

        echo "\r" . str_pad("Inserted: {$insertedTickets} tickets. Remaining: {$remainingTickets} tickets. Elapsed time: {$elapsedTime} seconds.", 100);
    }

    /**
     * Создает партицию для покупок билетов этой лотереи
     *
     * @throws Exception
     */
    private function createUserPurchasesPartition(PDO $pdo, int $lotteryId): void
    {
        $partitionName = "user_ticket_purchases_lottery_{$lotteryId}";
        $parentTable = 'user_ticket_purchases';

        // Проверяем, существует ли уже партиция
        if ($this->checkTable($pdo, $partitionName)) {
            Log::channel(self::LOG_CHANNEL)->info("Партиция {$partitionName} уже существует");
            return; // Партиция уже существует
        }

        Log::channel(self::LOG_CHANNEL)->info("Создаем партицию для покупок билетов", [
            'lottery_id' => $lotteryId,
            'partition_name' => $partitionName
        ]);

        try {
            // Стратегия LIKE + ATTACH PARTITION для минимизации блокировок

            // 1. Создаем таблицу отдельно (НЕ блокирует родительскую таблицу)
            $createQuery = "
                CREATE TABLE {$partitionName} 
                (LIKE {$parentTable} INCLUDING ALL)
            ";
            $pdo->exec($createQuery);

            // 2. Добавляем CHECK constraint для валидации при ATTACH
            $constraintName = "check_lottery_{$lotteryId}";
            $constraintQuery = "
                ALTER TABLE {$partitionName} 
                ADD CONSTRAINT {$constraintName} 
                CHECK (lottery_id = {$lotteryId})
            ";
            $pdo->exec($constraintQuery);

            // 3. Присоединяем партицию (минимальная блокировка: SHARE UPDATE EXCLUSIVE)
            // Для RANGE партиционирования используем FROM/TO
            $nextLotteryId = $lotteryId + 1;
            $attachQuery = "
                ALTER TABLE {$parentTable} 
                ATTACH PARTITION {$partitionName} 
                FOR VALUES FROM ({$lotteryId}) TO ({$nextLotteryId})
            ";
            $pdo->exec($attachQuery);

            // 4. Удаляем теперь избыточный CHECK constraint
            $dropConstraintQuery = "
                ALTER TABLE {$partitionName} 
                DROP CONSTRAINT {$constraintName}
            ";
            $pdo->exec($dropConstraintQuery);

            Log::channel(self::LOG_CHANNEL)->info("Партиция успешно создана", [
                'lottery_id' => $lotteryId,
                'partition_name' => $partitionName
            ]);
        } catch (Exception $e) {
            // В случае ошибки, очищаем созданную таблицу
            try {
                $pdo->exec("DROP TABLE IF EXISTS {$partitionName}");
            } catch (Exception $cleanupException) {
                // Игнорируем ошибки очистки
            }

            Log::channel(self::LOG_CHANNEL)->error("Ошибка создания партиции", [
                'lottery_id' => $lotteryId,
                'partition_name' => $partitionName,
                'error' => $e->getMessage()
            ]);

            throw new Exception("Ошибка создания партиции {$partitionName}: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Создает партицию для выигрышных билетов этой лотереи
     *
     * @throws Exception
     */
    private function createWinnerTicketsPartition(PDO $pdo, int $lotteryId): void
    {
        $partitionName = "winner_tickets_lottery_{$lotteryId}";
        $parentTable = 'winner_tickets';

        // Проверяем, существует ли уже партиция
        if ($this->checkTable($pdo, $partitionName)) {
            Log::channel(self::LOG_CHANNEL)->info("Партиция {$partitionName} уже существует");
            return; // Партиция уже существует
        }

        Log::channel(self::LOG_CHANNEL)->info("Создаем партицию для выигрышных билетов", [
            'lottery_id' => $lotteryId,
            'partition_name' => $partitionName
        ]);

        try {
            // Стратегия LIKE + ATTACH PARTITION для минимизации блокировок

            // 1. Создаем таблицу отдельно (НЕ блокирует родительскую таблицу)
            $createQuery = "
                CREATE TABLE {$partitionName} 
                (LIKE {$parentTable} INCLUDING ALL)
            ";
            $pdo->exec($createQuery);

            // 2. Добавляем CHECK constraint для валидации при ATTACH
            $constraintName = "check_lottery_{$lotteryId}";
            $constraintQuery = "
                ALTER TABLE {$partitionName} 
                ADD CONSTRAINT {$constraintName} 
                CHECK (lottery_id = {$lotteryId})
            ";
            $pdo->exec($constraintQuery);

            // 3. Присоединяем партицию (минимальная блокировка: SHARE UPDATE EXCLUSIVE)
            // Для RANGE партиционирования используем FROM/TO
            $nextLotteryId = $lotteryId + 1;
            $attachQuery = "
                ALTER TABLE {$parentTable} 
                ATTACH PARTITION {$partitionName} 
                FOR VALUES FROM ({$lotteryId}) TO ({$nextLotteryId})
            ";
            $pdo->exec($attachQuery);

            // 4. Удаляем теперь избыточный CHECK constraint
            $dropConstraintQuery = "
                ALTER TABLE {$partitionName} 
                DROP CONSTRAINT {$constraintName}
            ";
            $pdo->exec($dropConstraintQuery);

            Log::channel(self::LOG_CHANNEL)->info("Партиция успешно создана", [
                'lottery_id' => $lotteryId,
                'partition_name' => $partitionName
            ]);
        } catch (Exception $e) {
            // В случае ошибки, очищаем созданную таблицу
            try {
                $pdo->exec("DROP TABLE IF EXISTS {$partitionName}");
            } catch (Exception $cleanupException) {
                // Игнорируем ошибки очистки
            }

            Log::channel(self::LOG_CHANNEL)->error("Ошибка создания партиции", [
                'lottery_id' => $lotteryId,
                'partition_name' => $partitionName,
                'error' => $e->getMessage()
            ]);

            throw new Exception("Ошибка создания партиции {$partitionName}: " . $e->getMessage(), 0, $e);
        }
    }
}
