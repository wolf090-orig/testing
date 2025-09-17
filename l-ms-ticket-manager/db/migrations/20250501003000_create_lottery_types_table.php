<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * НОВАЯ АРХИТЕКТУРА: Типы лотерей (daily_fixed, daily_dynamic, jackpot, supertour)
 * Расширенная структура с категориями, типами расчета призов и ограничениями
 */
final class CreateLotteryTypesTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('lottery_types');
        $table->addColumn('name', 'string', ['limit' => 20])
            ->addColumn('description', 'string', ['limit' => 255])
            ->addColumn('category', 'string', ['limit' => 50])
            ->addColumn('prize_calculation_type', 'string', ['limit' => 20, 'comment' => 'Тип расчета призов: fixed, dynamic, percentage, accumulation'])
            ->addColumn('duration_days', 'integer', ['null' => true])
            ->addColumn('max_tickets_count', 'integer', ['null' => true])
            ->addIndex(['name'], ['unique' => true])
            ->addIndex(['category'])
            ->addTimestamps()
            ->create();

        // Добавляем check constraint для ограничения значений prize_calculation_type
        $this->execute("ALTER TABLE lottery_types ADD CONSTRAINT check_prize_calculation_type CHECK (prize_calculation_type IN ('fixed', 'dynamic', 'percentage', 'accumulation'))");
    }
}
