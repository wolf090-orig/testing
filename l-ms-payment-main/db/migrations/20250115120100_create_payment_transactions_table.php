<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * ОСНОВНАЯ ТАБЛИЦА: Транзакции платежей
 * Хранит все заказы на оплату от тикет-менеджера
 */
final class CreatePaymentTransactionsTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('payment_transactions', ['id' => false, 'primary_key' => 'id']);
        
        $table
            // Первичный ключ
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            
            // Основные данные транзакции
            ->addColumn('internal_order_id', 'string', ['limit' => 255, 'null' => false, 'comment' => 'Уникальный ID покупки от тикет-менеджера'])
            ->addColumn('external_transaction_id', 'string', ['limit' => 255, 'null' => true, 'comment' => 'ID транзакции в платёжном шлюзе'])
            ->addColumn('user_id', 'string', ['limit' => 255, 'null' => false, 'comment' => 'ID пользователя'])
            ->addColumn('amount', 'decimal', ['precision' => 15, 'scale' => 2, 'null' => false, 'comment' => 'Сумма операции'])
            ->addColumn('currency', 'string', ['limit' => 3, 'null' => false, 'comment' => 'Валюта операции'])
            ->addColumn('payment_method', 'string', ['limit' => 10, 'null' => false, 'comment' => 'Способ оплаты: card, sbp'])
            ->addColumn('status', 'string', ['limit' => 20, 'default' => 'created', 'null' => false, 'comment' => 'Статус транзакции: created, processing, success, failed, expired'])
            
            // Статус обработки
            ->addColumn('payment_completed', 'boolean', ['default' => false, 'null' => false, 'comment' => 'Завершен ли платеж'])
            ->addColumn('gateway_request_attempts', 'integer', ['default' => 0, 'null' => false, 'comment' => 'Количество попыток запросов в шлюз'])
            
            // Временные метки (в конце)
            ->addColumn('last_status_check', 'timestamp', ['null' => true, 'comment' => 'Время последней проверки статуса'])
            ->addTimestamps()
            
            // Индексы
            ->addIndex(['internal_order_id'], ['unique' => true, 'name' => 'idx_payment_transactions_internal_order_id'])
            ->addIndex(['external_transaction_id'], ['name' => 'idx_payment_transactions_external_transaction_id'])
            ->addIndex(['payment_completed'], ['name' => 'idx_payment_transactions_payment_completed'])
            ->addIndex(['status'], ['name' => 'idx_payment_transactions_status'])
            ->addIndex(['last_status_check'], ['name' => 'idx_payment_transactions_last_check'])
            
            ->create();
            
        // Добавляем колонку transaction_type с enum типом
        $this->execute("ALTER TABLE payment_transactions ADD COLUMN transaction_type transaction_type NOT NULL");
        
        // Добавляем индекс на transaction_type после создания колонки
        $this->execute("CREATE INDEX idx_payment_transactions_type ON payment_transactions (transaction_type, created_at)");
    }
}
