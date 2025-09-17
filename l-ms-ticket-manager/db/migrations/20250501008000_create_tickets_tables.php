<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Создание партиционированных таблиц для билетов различных типов лотерей
 * Использует современное партиционирование PostgreSQL PARTITION BY
 */
final class CreateTicketsTables extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change(): void
    {
        // Создаем партиционированные таблицы для каждого типа лотереи
        $this->createPartitionedTicketTable('daily_fixed_tickets');
        $this->createPartitionedTicketTable('daily_dynamic_tickets');
        $this->createPartitionedTicketTable('jackpot_tickets');
        $this->createPartitionedTicketTable('supertour_tickets');
    }

    /**
     * Создает партиционированную таблицу билетов с PARTITION BY
     */
    private function createPartitionedTicketTable(string $tableName): void
    {
        // Создаем партиционированную таблицу через execute, так как Phinx не поддерживает PARTITION BY
        $sql = "
            CREATE TABLE {$tableName} (
                id SERIAL,
                ticket_number VARCHAR(20) NOT NULL,
                lottery_id INTEGER NOT NULL,
                lottery_type_id INTEGER NOT NULL,
                is_paid BOOLEAN DEFAULT FALSE,
                is_reserved BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
                updated_at TIMESTAMP,
                PRIMARY KEY (id, lottery_id),
                UNIQUE (ticket_number, lottery_id)
            ) PARTITION BY LIST (lottery_id);
        ";

        $this->execute($sql);

        // Добавляем индексы
        $this->execute("CREATE INDEX ON {$tableName} (lottery_id);");
        $this->execute("CREATE INDEX ON {$tableName} (lottery_type_id);");
        $this->execute("CREATE INDEX ON {$tableName} (is_paid);");
        $this->execute("CREATE INDEX ON {$tableName} (is_reserved);");

        // Добавляем внешние ключи
        $this->execute("
            ALTER TABLE {$tableName} 
            ADD CONSTRAINT {$tableName}_lottery_id_fkey 
            FOREIGN KEY (lottery_id) REFERENCES lottery_numbers(id) 
            ON UPDATE CASCADE ON DELETE CASCADE;
        ");

        $this->execute("
            ALTER TABLE {$tableName} 
            ADD CONSTRAINT {$tableName}_lottery_type_id_fkey 
            FOREIGN KEY (lottery_type_id) REFERENCES lottery_types(id) 
            ON UPDATE CASCADE ON DELETE RESTRICT;
        ");

        // Добавляем комментарии
        $this->execute("COMMENT ON COLUMN {$tableName}.ticket_number IS 'Номер билета с префиксом страны и ID лотереи (RU0000001_L2)';");
        $this->execute("COMMENT ON COLUMN {$tableName}.lottery_id IS 'ID лотереи из lottery_numbers';");
        $this->execute("COMMENT ON COLUMN {$tableName}.lottery_type_id IS 'ID типа лотереи для партиционирования';");
        $this->execute("COMMENT ON COLUMN {$tableName}.is_paid IS 'Оплачен ли билет';");
        $this->execute("COMMENT ON COLUMN {$tableName}.is_reserved IS 'Зарезервирован ли билет в корзине';");
    }
}
