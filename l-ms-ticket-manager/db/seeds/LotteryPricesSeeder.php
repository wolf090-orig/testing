<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class LotteryPricesSeeder extends AbstractSeed
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

        $prices = [
            // Цены в рублях для всех типов
            ['lottery_type_id' => $typeMap['daily_fixed'], 'currency_id' => $currencyMap['RUB'], 'price' => 100],
            ['lottery_type_id' => $typeMap['daily_dynamic'], 'currency_id' => $currencyMap['RUB'], 'price' => 100],
            ['lottery_type_id' => $typeMap['jackpot'], 'currency_id' => $currencyMap['RUB'], 'price' => 200],
            ['lottery_type_id' => $typeMap['supertour'], 'currency_id' => $currencyMap['RUB'], 'price' => 500],

            // Цены в монетах только для daily_fixed
            ['lottery_type_id' => $typeMap['daily_fixed'], 'currency_id' => $currencyMap['COINS'], 'price' => 50],
        ];

        $this->insertIfNotExistsComplex('lottery_prices', $prices, ['lottery_type_id', 'currency_id']);
    }

    /**
     * Insert data into the table if it does not already exist based on multiple columns.
     *
     * @param string $table The name of the table
     * @param array $data The data to insert
     * @param array $uniqueColumns The columns to check for uniqueness
     */
    private function insertIfNotExistsComplex(string $table, array $data, array $uniqueColumns): void
    {
        $existingRows = $this->fetchAll("SELECT " . implode(', ', $uniqueColumns) . " FROM {$table}");

        $filteredData = [];
        foreach ($data as $row) {
            $exists = false;
            foreach ($existingRows as $existingRow) {
                $match = true;
                foreach ($uniqueColumns as $column) {
                    if ($existingRow[$column] != $row[$column]) {
                        $match = false;
                        break;
                    }
                }
                if ($match) {
                    $exists = true;
                    break;
                }
            }

            if (!$exists) {
                $filteredData[] = $row;
            }
        }

        if (!empty($filteredData)) {
            $this->table($table)
                ->insert($filteredData)
                ->saveData();
        }
    }
}
