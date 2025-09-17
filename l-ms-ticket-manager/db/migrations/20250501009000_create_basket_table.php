<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Создание таблицы корзин пользователей с полным управлением состоянием
 * Включает временное управление, статусы платежей и блокировки
 */
final class CreateBasketTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        // Создаем таблицу user_baskets
        $table = $this->table('user_baskets');
        $table->addColumn('start_date', 'datetime', ['comment' => 'Дата создания корзины'])
            ->addColumn('end_date', 'datetime', ['comment' => 'Дата истечения корзины (обычно +15 минут)'])
            ->addColumn('user_id', 'biginteger', ['null' => false, 'comment' => 'ID пользователя'])
            ->addColumn('cancel_reason_id', 'integer', ['null' => true, 'comment' => 'Причина закрытия корзины'])
            ->addForeignKey('cancel_reason_id', 'cancel_reasons', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
            ->addColumn('payment_status_id', 'integer', ['null' => true, 'comment' => 'Статус оплаты'])
            ->addForeignKey('payment_status_id', 'payment_statuses', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
            ->addColumn('transaction_id', 'string', ['limit' => 50, 'null' => true, 'comment' => 'ID транзакции оплаты'])
            ->addColumn('is_payment_lock', 'boolean', ['default' => false, 'comment' => 'Блокировка для предотвращения двойной оплаты'])
            ->addColumn('payment_lock_count', 'integer', ['default' => 0, 'comment' => 'Счетчик попыток оплаты'])
            ->addIndex(['user_id'])
            ->addIndex(['end_date'])
            ->addIndex(['cancel_reason_id'])
            ->addIndex(['payment_status_id'])
            ->addIndex(['is_payment_lock'])
            ->addTimestamps()
            ->create();
    }
}
