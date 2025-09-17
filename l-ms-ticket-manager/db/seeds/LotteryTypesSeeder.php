<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class LotteryTypesSeeder extends AbstractSeed
{
    /**
     * Run Method.
     */
    public function run(): void
    {
        $data = [
            [
                'name' => 'daily_fixed',
                'description' => 'Ежедневная лотерея с фиксированным призом',
                'category' => 'daily',
                'prize_calculation_type' => 'fixed',
                'duration_days' => 1,
                'max_tickets_count' => null
            ],
            [
                'name' => 'daily_dynamic',
                'description' => 'Ежедневная лотерея с динамическим призовым фондом',
                'category' => 'daily',
                'prize_calculation_type' => 'dynamic',
                'duration_days' => 1,
                'max_tickets_count' => null
            ],
            [
                'name' => 'jackpot',
                'description' => 'Джекпот лотерея с накопительным призовым фондом',
                'category' => 'special',
                'prize_calculation_type' => 'accumulation',
                'duration_days' => 15,
                'max_tickets_count' => null
            ],
            [
                'name' => 'supertour',
                'description' => 'Супертур с крупными призами',
                'category' => 'special',
                'prize_calculation_type' => 'fixed',
                'duration_days' => 365,
                'max_tickets_count' => 5000
            ]
        ];

        $this->insertIfNotExists('lottery_types', $data, 'name');
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
}
