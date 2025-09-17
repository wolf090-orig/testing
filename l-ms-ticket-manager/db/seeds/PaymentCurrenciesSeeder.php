<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class PaymentCurrenciesSeeder extends AbstractSeed
{
    /**
     * Run Method.
     */
    public function run(): void
    {
        $data = [
            ['code' => 'RUB', 'name' => 'Российский рубль'],
            ['code' => 'COINS', 'name' => 'Внутренние монеты'],
        ];

        $this->insertIfNotExists('payment_currencies', $data, 'code');
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
