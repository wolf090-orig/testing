<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * ТАБЛИЦА: Callbacks от платежного шлюза
 * Хранит все входящие webhook'и от FPGate
 */
final class CreatePaymentCallbacksTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('payment_callbacks', ['id' => false, 'primary_key' => 'id']);
        
        $table
            // Первичный ключ
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            
            // Основные данные callback'а
            ->addColumn('external_transaction_id', 'string', ['limit' => 255, 'null' => false, 'comment' => 'ID транзакции в платёжном шлюзе'])
            ->addColumn('order_id', 'string', ['limit' => 255, 'null' => false, 'comment' => 'Уникальный ID покупки (наш internal_order_id)'])
            ->addColumn('amount', 'decimal', ['precision' => 15, 'scale' => 2, 'null' => false, 'comment' => 'Сумма операции'])
            ->addColumn('currency', 'string', ['limit' => 3, 'null' => false, 'comment' => 'Валюта операции (RUB, USD, EUR)'])
            ->addColumn('recalculated', 'boolean', ['default' => false, 'null' => false, 'comment' => 'Флаг пересчёта платежа'])
            ->addColumn('status_type', 'string', ['limit' => 20, 'null' => false, 'comment' => 'created, success, cancelled, processing, error'])
            
            // Полные данные callback'а
            ->addColumn('callback_data', 'json', ['null' => false, 'comment' => 'Полные данные callback (включая token, signature)'])
            
            // Статус обработки
            ->addColumn('processed', 'boolean', ['default' => false, 'null' => false, 'comment' => 'Обработан ли callback'])
            ->addColumn('kafka_sent', 'boolean', ['default' => false, 'null' => false, 'comment' => 'Отправлен ли в Kafka'])
            
            // Временные метки (в конце)
            ->addColumn('callback_timestamp', 'timestamp', ['null' => false, 'comment' => 'Время callback от FPGate'])
            ->addTimestamps()
            
            // Уникальность callback'ов
            ->addIndex(['external_transaction_id', 'order_id', 'callback_timestamp'], [
                'unique' => true, 
                'name' => 'idx_payment_callbacks_unique'
            ])
            
            // Индексы для поиска
            ->addIndex(['external_transaction_id'], ['name' => 'idx_payment_callbacks_external_transaction_id'])
            ->addIndex(['order_id'], ['name' => 'idx_payment_callbacks_order_id'])
            ->addIndex(['processed'], ['name' => 'idx_payment_callbacks_processed'])
            ->addIndex(['kafka_sent'], ['name' => 'idx_payment_callbacks_kafka_sent'])
            
            ->create();
        
        // Добавляем внешний ключ после создания таблицы
        $this->table('payment_callbacks')
            ->addForeignKey('order_id', 'payment_transactions', 'internal_order_id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
                'constraint' => 'fk_callbacks_transaction'
            ])
            ->update();
    }
}
