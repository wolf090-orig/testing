<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * НОВАЯ АРХИТЕКТУРА: Расписания типов лотерей
 * Времена продаж и розыгрышей с возможностью включения/выключения типов
 * Поддержка нескольких расписаний для одного типа (например, daily_dynamic несколько раз в день)
 */
final class CreateLotteryTypesSchedulesTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('lottery_types_schedules');
        $table->addColumn('type_id', 'integer')
            ->addColumn('schedule_name', 'string', ['limit' => 50, 'comment' => 'Название расписания: Утренняя, Дневная, Вечерняя'])
            ->addColumn('sale_start_time', 'time', ['default' => '00:00:00'])
            ->addColumn('sale_end_time', 'time', ['default' => '23:00:00'])
            ->addColumn('draw_time', 'time', ['default' => '23:30:00'])
            ->addColumn('is_active', 'boolean', ['default' => true, 'comment' => 'Создавать ли лотереи этого расписания автоматически'])
            ->addForeignKey('type_id', 'lottery_types', 'id', ['delete' => 'RESTRICT', 'update' => 'RESTRICT'])
            ->addIndex(['type_id'])
            ->addIndex(['is_active'])
            ->addIndex(['type_id', 'schedule_name'], ['unique' => true])
            ->addTimestamps()
            ->create();
    }
}
