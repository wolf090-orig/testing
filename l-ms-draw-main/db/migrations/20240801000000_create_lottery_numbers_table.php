<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Создание таблицы lottery_numbers по аналогии с ms-ticket-manager
 * Основная таблица лотерей для ms-draw-service
 */
final class CreateLotteryNumbersTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('lottery_numbers');
        $table->addColumn('lottery_type', 'string', ['limit' => 50, 'comment' => 'Тип лотереи: daily_fixed, daily_dynamic, jackpot, supertour'])
            ->addColumn('lottery_name', 'string', ['limit' => 50, 'comment' => 'Название лотереи'])
            ->addColumn('start_date', 'datetime', ['comment' => 'Дата начала продажи билетов'])
            ->addColumn('end_date', 'datetime', ['comment' => 'Дата окончания продажи билетов'])
            ->addColumn('draw_date', 'datetime', ['comment' => 'Дата проведения розыгрыша'])
            ->addColumn('drawn_at', 'timestamp', ['null' => true, 'comment' => 'Время проведения розыгрыша'])
            ->addColumn('is_active', 'boolean', ['default' => true, 'comment' => 'Активна ли лотерея'])
            ->addColumn('calculated_winners_count', 'integer', ['null' => true, 'comment' => 'Рассчитанное количество победителей от ms-ticket-manager'])
            ->addColumn('total_participants', 'integer', ['null' => true, 'comment' => 'Общее количество участников'])
            ->addColumn('total_tickets_sold', 'integer', ['null' => true, 'comment' => 'Общее количество проданных билетов'])
            ->addIndex(['lottery_type'])
            ->addIndex(['draw_date'])
            ->addIndex(['drawn_at'])
            ->addIndex(['is_active'])
            ->addIndex(['lottery_type', 'drawn_at'])
            ->addTimestamps()
            ->create();

        // Добавляем ограничение на тип лотереи
        $this->execute("
            ALTER TABLE lottery_numbers 
            ADD CONSTRAINT check_lottery_type 
            CHECK (lottery_type IN ('daily_fixed', 'daily_dynamic', 'jackpot', 'supertour'))
        ");
    }
}
