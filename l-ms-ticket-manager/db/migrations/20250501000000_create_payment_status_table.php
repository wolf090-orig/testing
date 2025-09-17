<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * СПРАВОЧНИК: Статусы платежей
 * Справочник статусов платежей (оплачен, отменен и т.д.)
 */
final class CreatePaymentStatusTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        // create the table
        $table = $this->table('payment_statuses');
        $table->addColumn('name', 'string', ['limit' => 255])
            ->addColumn('description', 'string', ['limit' => 255])
            ->addTimestamps()
            ->create();
    }
}
