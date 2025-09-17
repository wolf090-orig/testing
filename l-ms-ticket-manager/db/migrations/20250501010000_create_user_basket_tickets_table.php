<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Связь билетов с корзинами пользователей
 * Временное резервирование билетов в корзинах перед покупкой
 */
final class CreateUserBasketTicketsTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('user_basket_tickets');
        $table->addColumn('basket_id', 'integer', ['comment' => 'ID корзины пользователя'])
            ->addColumn('ticket_id', 'integer', ['comment' => 'ID билета в партиционированной таблице'])
            ->addColumn('lottery_id', 'integer', ['comment' => 'ID лотереи для денормализации'])
            ->addForeignKey('basket_id', 'user_baskets', 'id', ['delete' => 'CASCADE', 'update' => 'RESTRICT'])
            ->addForeignKey('lottery_id', 'lottery_numbers', 'id', ['delete' => 'RESTRICT', 'update' => 'RESTRICT'])
            ->addIndex(['basket_id'])
            ->addIndex(['lottery_id'])
            ->addIndex(['ticket_id', 'lottery_id'], ['unique' => true])
            ->addTimestamps()
            ->create();

        // Создаем индексы
        $this->execute("CREATE INDEX idx_user_basket_tickets_basket_id ON user_basket_tickets (basket_id);");
        $this->execute("CREATE INDEX idx_user_basket_tickets_lottery_id ON user_basket_tickets (lottery_id);");
        $this->execute("CREATE INDEX idx_user_basket_tickets_basket_lottery ON user_basket_tickets (basket_id, lottery_id);");
    }
}
