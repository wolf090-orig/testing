<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * СПРАВОЧНИК: Причины отмены
 * Справочник причин отмены заказов/платежей
 */
final class CreateCancelReasonTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        // create the table
        $table = $this->table('cancel_reasons');
        $table->addColumn('name', 'string', ['limit' => 255])
            ->addColumn('description', 'string', ['limit' => 255])
            ->addTimestamps()
            ->create();
    }
}
