<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Создание партиционированных таблиц для билетов по типам лотерей
 * Точно как в ms-ticket-manager - партиционирование по lottery_id
 */
final class CreatePartitionedTicketsTables extends AbstractMigration
{
    public function change(): void
    {
        // Создаем партиционированные таблицы для каждого типа лотереи
        $this->createPartitionedTicketTable('daily_fixed_tickets');
        $this->createPartitionedTicketTable('daily_dynamic_tickets');
        $this->createPartitionedTicketTable('jackpot_tickets');
        $this->createPartitionedTicketTable('supertour_tickets');
    }

    /**
     * Создает партиционированную таблицу билетов с PARTITION BY lottery_id
     */
    private function createPartitionedTicketTable(string $tableName): void
    {
        // Создаем партиционированную таблицу через execute, так как Phinx не поддерживает PARTITION BY
        $sql = "
            CREATE TABLE {$tableName} (
                id SERIAL,
                ticket_number VARCHAR(20) NOT NULL,
                lottery_id INTEGER NOT NULL,
                winner_position INTEGER NULL,
                is_winner BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
                PRIMARY KEY (id, lottery_id),
                UNIQUE (ticket_number, lottery_id)
            ) PARTITION BY LIST (lottery_id);
        ";

        $this->execute($sql);

        // Добавляем индексы для производительности
        $this->execute("CREATE INDEX ON {$tableName} (lottery_id);");
        $this->execute("CREATE INDEX ON {$tableName} (is_winner);");
        $this->execute("CREATE INDEX ON {$tableName} (winner_position) WHERE winner_position IS NOT NULL;");
        $this->execute("CREATE INDEX ON {$tableName} (ticket_number);");

        // Добавляем внешний ключ на lottery_numbers
        $this->execute("
            ALTER TABLE {$tableName} 
            ADD CONSTRAINT {$tableName}_lottery_id_fkey 
            FOREIGN KEY (lottery_id) REFERENCES lottery_numbers(id) 
            ON UPDATE CASCADE ON DELETE CASCADE;
        ");

        // Добавляем комментарии для документации
        $this->execute("COMMENT ON TABLE {$tableName} IS 'Партиционированная таблица билетов для типа лотереи';");
        $this->execute("COMMENT ON COLUMN {$tableName}.ticket_number IS 'Номер билета от ms-ticket-manager';");
        $this->execute("COMMENT ON COLUMN {$tableName}.lottery_id IS 'ID лотереи для партиционирования';");
        $this->execute("COMMENT ON COLUMN {$tableName}.winner_position IS 'Позиция победителя (1, 2, 3...)';");
        $this->execute("COMMENT ON COLUMN {$tableName}.is_winner IS 'Является ли билет выигрышным';");
    }
} 