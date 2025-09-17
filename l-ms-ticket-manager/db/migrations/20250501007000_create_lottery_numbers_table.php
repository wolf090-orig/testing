<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * НОВАЯ АРХИТЕКТУРА: Лотереи с конкретными датами
 * Основная таблица лотерей с фиксацией настроек призов и управлением активностью
 */
final class CreateLotteryNumbersTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('lottery_numbers');
        $table->addColumn('country_id', 'integer', [
            'null' => false,
            'comment' => 'ID страны для привязки лотереи'
        ])
            ->addColumn('lottery_type_id', 'integer')
            ->addColumn('lottery_name', 'string', ['limit' => 50])
            ->addColumn('start_date', 'datetime', ['comment' => 'Дата начала продажи билетов'])
            ->addColumn('end_date', 'datetime', ['comment' => 'Дата окончания продажи билетов'])
            ->addColumn('draw_date', 'datetime', ['comment' => 'Дата проведения розыгрыша'])
            ->addColumn('is_drawn', 'boolean', ['default' => false])
            ->addColumn('is_active', 'boolean', ['default' => true, 'comment' => 'Админ может выключить лотерею вручную'])
            ->addColumn('is_prize_config_locked', 'boolean', ['default' => false, 'comment' => 'Фиксация настроек призов'])
            ->addColumn('is_tickets_generation_completed', 'boolean', ['default' => false, 'comment' => 'Все билеты сгенерированы, больше не создавать'])
            ->addColumn('prize_configuration_id', 'integer', ['null' => true, 'comment' => 'Ссылка на базовую конфигурацию'])
            ->addColumn('calculated_winners_count', 'integer', ['null' => true, 'comment' => 'Рассчитанное количество победителей после закрытия продаж'])
            ->addColumn('schedule_exported_at', 'datetime', ['null' => true, 'comment' => 'Дата отправки расписания лотереи в Kafka'])
            ->addColumn('winners_config_exported_at', 'datetime', ['null' => true, 'comment' => 'Дата отправки конфигурации победителей в Kafka'])
            ->addForeignKey('country_id', 'countries', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
            ->addForeignKey('lottery_type_id', 'lottery_types', 'id', ['delete' => 'RESTRICT', 'update' => 'RESTRICT'])
            ->addForeignKey('prize_configuration_id', 'prize_configurations', 'id', ['delete' => 'SET_NULL', 'update' => 'RESTRICT'])
            // Основные индексы
            ->addIndex(['country_id'])
            ->addIndex(['lottery_type_id'])
            ->addIndex(['start_date'])
            ->addIndex(['end_date'])
            ->addIndex(['draw_date'])
            ->addIndex(['is_active'])
            ->addIndex(['schedule_exported_at'])
            ->addIndex(['winners_config_exported_at'])
            ->addTimestamps()
            ->create();
    }
}
