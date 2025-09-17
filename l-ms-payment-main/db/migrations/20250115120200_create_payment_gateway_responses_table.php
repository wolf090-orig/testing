<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * ТАБЛИЦА: Ответы от платежного шлюза
 * Хранит все ответы от FPGate для обработки разрывов соединения
 */
final class CreatePaymentGatewayResponsesTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('payment_gateway_responses', ['id' => false, 'primary_key' => 'id']);
        
        $table
            // Первичный ключ
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            
            // Связь с транзакцией
            ->addColumn('internal_order_id', 'string', ['limit' => 255, 'null' => false, 'comment' => 'Наш внутренний ID заказа'])
            ->addColumn('external_transaction_id', 'string', ['limit' => 255, 'null' => true, 'comment' => 'ID транзакции в шлюзе (если успешно)'])
            
            // HTTP ответ
            ->addColumn('http_status_code', 'integer', ['null' => false, 'comment' => 'HTTP код ответа (200, 400, 500 и т.д.)'])
            ->addColumn('response_data', 'json', ['null' => false, 'comment' => 'Полный ответ от шлюза'])
            
            // Статус обработки
            ->addColumn('is_successful', 'boolean', ['default' => false, 'null' => false, 'comment' => 'Успешен ли ответ (200 + success в JSON)'])
            ->addColumn('has_payment_details', 'boolean', ['default' => false, 'null' => false, 'comment' => 'Есть ли реквизиты для оплаты'])
            
            // Временные метки
            ->addTimestamps()
            
            // Индексы
            ->addIndex(['internal_order_id'], ['name' => 'idx_payment_gateway_responses_order_id'])
            ->addIndex(['external_transaction_id'], ['name' => 'idx_payment_gateway_responses_transaction_id'])
            ->addIndex(['is_successful', 'has_payment_details'], ['name' => 'idx_payment_gateway_responses_successful'])
            
            ->create();
        
        // Добавляем внешний ключ после создания таблицы
        $this->table('payment_gateway_responses')
            ->addForeignKey('internal_order_id', 'payment_transactions', 'internal_order_id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
                'constraint' => 'fk_gateway_responses_transaction'
            ])
            ->update();
    }
}
