<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Таблица шаблонов настроек призов
 */
final class CreatePrizeConfigurationsTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        // Основная таблица шаблонов настроек призов
        $table = $this->table('prize_configurations');
        $table->addColumn('lottery_type_id', 'integer')
            ->addColumn('name', 'string', ['limit' => 100, 'comment' => 'Базовая конфигурация daily_fixed'])
            ->addColumn('description', 'text', ['null' => true])
            ->addColumn('is_active', 'boolean', ['default' => true])
            ->addColumn('positions_count', 'integer', ['comment' => 'Количество призовых мест'])
            ->addColumn('prize_fund_percentage', 'integer', [
                'comment' => 'Процент от выручки который идет в призовой фонд (например, 50 = 50%)',
                'default' => 50
            ])
            ->addColumn('dynamic_distribution_rules', 'json', ['null' => true, 'comment' => 'JSON с правилами динамического распределения призов'])
            ->addForeignKey('lottery_type_id', 'lottery_types', 'id', ['delete' => 'RESTRICT', 'update' => 'RESTRICT'])
            ->addIndex(['lottery_type_id'])
            ->addIndex(['is_active'])
            ->addTimestamps()
            ->create();

        // Добавляем валидацию для prize_fund_percentage
        $this->execute("
            ALTER TABLE prize_configurations 
            ADD CONSTRAINT check_prize_fund_percentage 
            CHECK (prize_fund_percentage > 0 AND prize_fund_percentage <= 100)
        ");

        // Добавляем валидацию для JSON структуры dynamic_distribution_rules
        $this->execute("
            ALTER TABLE prize_configurations 
            ADD CONSTRAINT check_dynamic_rules_structure 
            CHECK (
                dynamic_distribution_rules IS NULL OR (
                    dynamic_distribution_rules->>'algorithm' IN ('decreasing_percentage') AND
                    (dynamic_distribution_rules->>'after_position')::integer > 0 AND
                    (dynamic_distribution_rules->>'start_percentage')::numeric > 0 AND
                    (dynamic_distribution_rules->>'start_percentage')::numeric <= 100 AND
                    (dynamic_distribution_rules->>'decrease_step')::numeric > 0 AND
                    (dynamic_distribution_rules->>'min_amount_rub')::integer > 0
                )
            )
        ");
    }
}
