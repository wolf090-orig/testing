<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class CountriesSeeder extends AbstractSeed
{
    /**
     * Run Method.
     */
    public function run(): void
    {
        // Данные для стран (активными только RU и UZ)
        $countries = [
            ['code' => 'RU', 'name' => 'Россия', 'sort_order' => 1, 'active' => true],
            ['code' => 'UZ', 'name' => 'Узбекистан', 'sort_order' => 2, 'active' => true],
            ['code' => 'BY', 'name' => 'Беларусь', 'sort_order' => 3, 'active' => false],
            ['code' => 'KZ', 'name' => 'Казахстан', 'sort_order' => 4, 'active' => false],
            ['code' => 'KG', 'name' => 'Кыргызстан', 'sort_order' => 5, 'active' => false],
            ['code' => 'TJ', 'name' => 'Таджикистан', 'sort_order' => 6, 'active' => false],
            ['code' => 'UA', 'name' => 'Украина', 'sort_order' => 7, 'active' => false],
        ];

        // Добавляем данные в таблицу, если они еще не существуют
        $this->insertIfNotExists('countries', $countries, 'code');
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
