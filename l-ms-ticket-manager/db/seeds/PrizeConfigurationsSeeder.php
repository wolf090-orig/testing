<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class PrizeConfigurationsSeeder extends AbstractSeed
{
    /**
     * Run Method.
     */
    public function run(): void
    {
        $types = $this->fetchAll('SELECT id, name FROM lottery_types');
        $currencies = $this->fetchAll('SELECT id, code FROM payment_currencies');

        $typeMap = array_column($types, 'id', 'name');
        $currencyMap = array_column($currencies, 'id', 'code');

        // Базовые конфигурации призов
        $configs = [
            [
                'lottery_type_id' => $typeMap['daily_fixed'],
                'name' => 'Базовая конфигурация Daily Fixed',
                'description' => 'Настраиваемый фиксированный приз одному победителю',
                'positions_count' => 1,
                'prize_fund_percentage' => 50, // 50% от выручки идет в призовой фонд
                'dynamic_distribution_rules' => null
            ],
            [
                'lottery_type_id' => $typeMap['daily_dynamic'], // Ссылка на тип лотереи daily_dynamic
                'name' => 'Базовая конфигурация Daily Dynamic', // Название: ежедневная лотерея с динамическим распределением
                'description' => 'Фиксированные топ-места + динамическое распределение остатка', // 3 фикс места + дополнительные
                'positions_count' => 3, // Количество ФИКСИРОВАННЫХ призовых мест (+ будут динамические)
                'prize_fund_percentage' => 50, // 50% от выручки = призовой фонд
                'dynamic_distribution_rules' => json_encode([ // JSON правила для расчета дополнительных мест
                    'after_position' => 3, // После какого места начинается динамическое распределение
                    'algorithm' => 'decreasing_percentage', // Алгоритм: убывающие проценты
                    'start_percentage' => 25, // Стартовый процент для 4-го места от фонда динамических мест
                    'decrease_step' => 3, // На сколько % уменьшается каждое следующее место (25%, 22%, 19%...)
                    'min_amount_rub' => 100, // Минимальная сумма приза в рублях (стоп-условие)
                    'base_fund_percentage' => 20, // Какой % от призового фонда идет на динамические места
                    'description' => 'После 3 места убывающие проценты от 20% остатка фонда' // Описание для админов
                ])
            ],
            [
                'lottery_type_id' => $typeMap['jackpot'],
                'name' => 'Базовая конфигурация Jackpot',
                'description' => 'Фиксированные топ-места + динамическое распределение остатка джекпота',
                'positions_count' => 4,
                'prize_fund_percentage' => 50, // 50% от выручки идет в призовой фонд
                'dynamic_distribution_rules' => json_encode([
                    'after_position' => 4,
                    'algorithm' => 'decreasing_percentage',
                    'start_percentage' => 4,
                    'decrease_step' => 3,
                    'min_amount_rub' => 100,
                    'base_fund_percentage' => 25,
                    'description' => 'После 4 места убывающие проценты от 25% остатка джекпота'
                ])
            ],
            [
                'lottery_type_id' => $typeMap['supertour'],
                'name' => 'Базовая конфигурация Supertour',
                'description' => 'Фиксированные призы суммой 1 млн рублей',
                'positions_count' => 10,
                'prize_fund_percentage' => 50, // 50% от выручки идет в призовой фонд
                'dynamic_distribution_rules' => null
            ]
        ];

        $this->insertIfNotExistsComplex('prize_configurations', $configs, ['lottery_type_id', 'name']);

        // Получаем созданные конфигурации (включая уже существующие)
        $createdConfigs = $this->fetchAll('SELECT id, lottery_type_id FROM prize_configurations');
        $configMap = [];
        foreach ($createdConfigs as $config) {
            foreach ($typeMap as $typeName => $typeId) {
                if ($config['lottery_type_id'] == $typeId) {
                    $configMap[$typeName] = $config['id'];
                    break;
                }
            }
        }

        // Проверяем, есть ли уже позиции для этих конфигураций
        $existingPositions = $this->fetchAll('SELECT prize_configuration_id, position FROM prize_configuration_positions');
        $existingConfigIds = array_unique(array_column($existingPositions, 'prize_configuration_id'));

        $positions = [];

        // Позиции для daily_fixed (денежный приз)
        if (!in_array($configMap['daily_fixed'], $existingConfigIds)) {
            $positions[] = [
                'prize_configuration_id' => $configMap['daily_fixed'],
                'position' => 1,
                'prize_type' => 'money',
                'prize_amount_rub' => 1000, // 1000 рублей
                'prize_percentage' => null,
                'prize_description' => '1000 руб за 1 место (настраиваемо)',
                'currency_id' => $currencyMap['RUB']
            ];
        }

        // Позиции для daily_dynamic (проценты от фонда + динамика)
        if (!in_array($configMap['daily_dynamic'], $existingConfigIds)) {
            $dynamicPositions = [
                [1, 40, '40% от призового фонда'],
                [2, 25, '25% от призового фонда'],
                [3, 15, '15% от призового фонда']
            ];

            foreach ($dynamicPositions as [$pos, $percentage, $desc]) {
                $positions[] = [
                    'prize_configuration_id' => $configMap['daily_dynamic'],
                    'position' => $pos,
                    'prize_type' => 'percentage',
                    'prize_amount_rub' => null,
                    'prize_percentage' => $percentage,
                    'prize_description' => $desc,
                    'currency_id' => null
                ];
            }
        }

        // Позиции для jackpot (проценты от фонда + динамика)
        if (!in_array($configMap['jackpot'], $existingConfigIds)) {
            $jackpotPositions = [
                [1, 30, '30% от джекпота'],
                [2, 20, '20% от джекпота'],
                [3, 15, '15% от джекпота'],
                [4, 10, '10% от джекпота']
            ];

            foreach ($jackpotPositions as [$pos, $percentage, $desc]) {
                $positions[] = [
                    'prize_configuration_id' => $configMap['jackpot'],
                    'position' => $pos,
                    'prize_type' => 'percentage',
                    'prize_amount_rub' => null,
                    'prize_percentage' => $percentage,
                    'prize_description' => $desc,
                    'currency_id' => null
                ];
            }
        }

        // Позиции для supertour (денежные и товарные)
        if (!in_array($configMap['supertour'], $existingConfigIds)) {
            $supertourPrizes = [
                [1, 'money', 200000, null, '200 тыс рублей за 1 место'],
                [2, 'money', 100000, null, '100 тыс рублей за 2 место'],
                [3, 'money', 70000, null, '70 тыс рублей за 3 место'],
                [4, 'product', null, null, 'iPhone 15 Pro'],
                [5, 'money', 50000, null, '50 тыс рублей'],
                [6, 'money', 25000, null, '25 тыс рублей'],
                [7, 'product', null, null, 'AirPods Pro'],
                [8, 'money', 10000, null, '10 тыс рублей'],
                [9, 'money', 5000, null, '5 тыс рублей'],
                [10, 'money', 2500, null, '2.5 тыс рублей']
            ];

            foreach ($supertourPrizes as [$pos, $type, $amount, $percentage, $desc]) {
                $positions[] = [
                    'prize_configuration_id' => $configMap['supertour'],
                    'position' => $pos,
                    'prize_type' => $type,
                    'prize_amount_rub' => $amount,
                    'prize_percentage' => $percentage,
                    'prize_description' => $desc,
                    'currency_id' => $amount ? $currencyMap['RUB'] : null
                ];
            }
        }

        if (!empty($positions)) {
            $this->table('prize_configuration_positions')->insert($positions)->saveData();
        }
    }

    /**
     * Insert data into the table if it does not already exist.
     *
     * @param string $table The name of the table
     * @param array $data The data to insert
     * @param string $uniqueColumn The column to check for uniqueness
     */
    private function insertIfNotExists(string $table, array $data, string $uniqueColumn): void
    {
        $existingRows = $this->fetchAll("SELECT {$uniqueColumn} FROM {$table}");
        $existingValues = array_column($existingRows, $uniqueColumn);

        $filteredData = array_filter($data, function ($row) use ($existingValues, $uniqueColumn) {
            return !in_array($row[$uniqueColumn], $existingValues);
        });

        if (!empty($filteredData)) {
            $this->table($table)
                ->insert($filteredData)
                ->saveData();
        }
    }

    /**
     * Insert data into the table if it does not already exist.
     *
     * @param string $table The name of the table
     * @param array $data The data to insert
     * @param array $uniqueColumns The columns to check for uniqueness
     */
    private function insertIfNotExistsComplex(string $table, array $data, array $uniqueColumns): void
    {
        $existingRows = $this->fetchAll("SELECT " . implode(', ', $uniqueColumns) . " FROM {$table}");
        $existingValues = array_map(function ($row) use ($uniqueColumns) {
            return array_map(function ($column) use ($row) {
                return $row[$column];
            }, $uniqueColumns);
        }, $existingRows);

        $filteredData = array_filter($data, function ($row) use ($existingValues, $uniqueColumns) {
            $rowValues = array_map(function ($column) use ($row) {
                return $row[$column];
            }, $uniqueColumns);
            return !in_array($rowValues, $existingValues);
        });

        if (!empty($filteredData)) {
            $this->table($table)
                ->insert($filteredData)
                ->saveData();
        }
    }
}
