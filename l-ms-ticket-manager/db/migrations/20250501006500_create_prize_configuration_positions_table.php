<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Таблица позиций призов в конфигурациях
 */
final class CreatePrizeConfigurationPositionsTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        // Детали позиций (1 место, 2 место и т.д.)
        $table = $this->table('prize_configuration_positions');
        $table->addColumn('prize_configuration_id', 'integer')
            ->addColumn('position', 'integer', ['comment' => '1, 2, 3...'])
            ->addColumn('prize_type', 'string', ['limit' => 20, 'comment' => 'Тип приза: money, product, percentage'])
            ->addColumn('prize_amount_rub', 'integer', ['null' => true, 'comment' => 'Приз в рублях (только для money)'])
            ->addColumn('prize_percentage', 'integer', ['null' => true, 'comment' => 'Процент от фонда 1-100 (только для percentage)'])
            ->addColumn('prize_description', 'string', ['limit' => 255, 'comment' => 'AirPods Pro, iPhone 15, 50% от фонда'])
            ->addColumn('currency_id', 'integer', ['null' => true])
            ->addForeignKey('prize_configuration_id', 'prize_configurations', 'id', ['delete' => 'CASCADE', 'update' => 'RESTRICT'])
            ->addForeignKey('currency_id', 'payment_currencies', 'id', ['delete' => 'RESTRICT', 'update' => 'RESTRICT'])
            ->addIndex(['prize_configuration_id', 'position'], ['unique' => true])
            ->addTimestamps()
            ->create();

        // Добавляем check constraint для ограничения значений prize_type
        $this->execute("ALTER TABLE prize_configuration_positions ADD CONSTRAINT check_prize_type CHECK (prize_type IN ('money', 'product', 'percentage'))");
    }
}
