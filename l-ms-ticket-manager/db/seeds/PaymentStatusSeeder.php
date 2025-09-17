<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class PaymentStatusSeeder extends AbstractSeed
{
    /**
     * Run Method.
     *
     * Write your database seeder using this method.
     *
     * More information on writing seeders is available here:
     * https://book.cakephp.org/phinx/0/en/seeding.html
     */
    public function run(): void
    {
        $types = [
            [
                'name' => 'paid',
                'description' => 'Оплата произведена',
            ],
            [
                'name' => 'pending',
                'description' => 'Ожидание оплаты',
            ],
            [
                'name' => 'failed',
                'description' => 'Ошибка при оплате',
            ],
            [
                'name' => 'refunded',
                'description' => 'Возврат средств',
            ],
        ];

        $this->insertIfNotExists('payment_statuses', $types);

        $cancels = [
            [
                'name' => 'expired',
                'description' => 'Истек срок оплаты',
            ],
            [
                'name' => 'canceled_by_user',
                'description' => 'Отменено пользователем',
            ],
            [
                'name' => 'payment_failed',
                'description' => 'Ошибка при оплате',
            ],
            [
                'name' => 'payment_success',
                'description' => 'Успешная оплата',
            ]
        ];

        $this->insertIfNotExists('cancel_reasons', $cancels);
    }

    /**
     * Insert data into the table if it does not already exist.
     *
     * @param string $table The name of the table
     * @param array $data The data to insert
     */
    private function insertIfNotExists(string $table, array $data): void
    {
        $existingRows = $this->fetchAll("SELECT name FROM {$table}");
        $existingNames = array_column($existingRows, 'name');

        $filteredData = array_filter($data, function ($row) use ($existingNames) {
            return !in_array($row['name'], $existingNames);
        });

        if (!empty($filteredData)) {
            $this->table($table)
                ->insert($filteredData)
                ->saveData();
        }
    }
}
