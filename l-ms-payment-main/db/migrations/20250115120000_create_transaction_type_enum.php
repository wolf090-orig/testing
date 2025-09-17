<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * ENUM: Типы транзакций (payin/payout)
 * Создает ENUM тип для определения направления платежа
 */
final class CreateTransactionTypeEnum extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        // Создаем ENUM тип для типов транзакций
        $this->execute("CREATE TYPE transaction_type AS ENUM ('payin', 'payout')");
    }
    
    /**
     * Rollback method for dropping the enum
     */
    public function down(): void
    {
        $this->execute("DROP TYPE IF EXISTS transaction_type");
    }
}
