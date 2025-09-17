<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * НОВАЯ АРХИТЕКТУРА: Цены билетов с поддержкой валют
 * INTEGER цены в рублях и монетах для разных типов лотерей
 */
final class CreateLotteryPricesTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('lottery_prices');
        $table->addColumn('lottery_type_id', 'integer')
            ->addColumn('currency_id', 'integer')
            ->addColumn('price', 'integer', ['comment' => 'В рублях для RUB, в единицах для COINS'])
            ->addForeignKey('lottery_type_id', 'lottery_types', 'id', ['delete' => 'RESTRICT', 'update' => 'RESTRICT'])
            ->addForeignKey('currency_id', 'payment_currencies', 'id', ['delete' => 'RESTRICT', 'update' => 'RESTRICT'])
            ->addIndex(['lottery_type_id', 'currency_id'], ['unique' => true])
            ->addTimestamps()
            ->create();
    }
}
