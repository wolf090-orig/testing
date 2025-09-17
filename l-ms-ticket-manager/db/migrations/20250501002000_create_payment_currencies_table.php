<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * НОВАЯ АРХИТЕКТУРА: Таблица валют (RUB, COINS)
 * Поддержка различных валют для оплаты билетов
 */
final class CreatePaymentCurrenciesTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('payment_currencies');
        $table->addColumn('code', 'string', ['limit' => 10])
            ->addColumn('name', 'string', ['limit' => 50])
            ->addIndex(['code'], ['unique' => true])
            ->addTimestamps()
            ->create();
    }
}
