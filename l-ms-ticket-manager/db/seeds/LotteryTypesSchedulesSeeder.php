<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class LotteryTypesSchedulesSeeder extends AbstractSeed
{
    /**
     * Run Method.
     */
    public function run(): void
    {
        $types = $this->fetchAll('SELECT id, name FROM lottery_types');
        $typeMap = array_column($types, 'id', 'name');

        $schedules = [];

        // Daily Fixed - 1 раз в день
        $schedules[] = [
            'type_id' => $typeMap['daily_fixed'],
            'schedule_name' => 'Основная',
            'sale_start_time' => '00:00:00',
            'sale_end_time' => '22:00:00',
            'draw_time' => '23:00:00',
            'is_active' => true
        ];

        // Daily Dynamic - 3 раза в день (все начинаются в 00:00)
        $schedules[] = [
            'type_id' => $typeMap['daily_dynamic'],
            'schedule_name' => 'Утренняя',
            'sale_start_time' => '00:00:00',
            'sale_end_time' => '11:00:00',
            'draw_time' => '12:00:00',
            'is_active' => true
        ];

        $schedules[] = [
            'type_id' => $typeMap['daily_dynamic'],
            'schedule_name' => 'Дневная',
            'sale_start_time' => '00:00:00',
            'sale_end_time' => '17:00:00',
            'draw_time' => '18:00:00',
            'is_active' => true
        ];

        $schedules[] = [
            'type_id' => $typeMap['daily_dynamic'],
            'schedule_name' => 'Вечерняя',
            'sale_start_time' => '00:00:00',
            'sale_end_time' => '22:00:00',
            'draw_time' => '23:00:00',
            'is_active' => true
        ];

        // Jackpot - длительная лотерея
        $schedules[] = [
            'type_id' => $typeMap['jackpot'],
            'schedule_name' => 'Основная',
            'sale_start_time' => '00:00:00',
            'sale_end_time' => '22:00:00',
            'draw_time' => '23:00:00',
            'is_active' => true
        ];

        // Supertour - длительная лотерея
        $schedules[] = [
            'type_id' => $typeMap['supertour'],
            'schedule_name' => 'Основная',
            'sale_start_time' => '00:00:00',
            'sale_end_time' => '22:00:00',
            'draw_time' => '23:00:00',
            'is_active' => true
        ];

        $this->insertIfNotExistsComplex('lottery_types_schedules', $schedules, ['type_id', 'schedule_name']);
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
