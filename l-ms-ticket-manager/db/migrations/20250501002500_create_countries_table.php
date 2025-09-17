<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Таблица стран для разделения лотерей по регионам
 * Аналогична таблице в ms-user для единообразия
 */
final class CreateCountriesTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('countries');
        $table->addColumn('code', 'string', ['limit' => 2, 'comment' => 'Код страны ISO 3166-1 alpha-2'])
            ->addColumn('name', 'string', ['limit' => 50, 'comment' => 'Название страны'])
            ->addColumn('sort_order', 'integer', ['null' => true, 'comment' => 'Порядок сортировки'])
            ->addColumn('active', 'boolean', ['default' => true, 'comment' => 'Активность страны для лотерей'])
            ->addIndex(['code'], ['unique' => true])
            ->addIndex(['active'])
            ->addIndex(['sort_order'])
            ->addTimestamps()
            ->create();
    }
}
