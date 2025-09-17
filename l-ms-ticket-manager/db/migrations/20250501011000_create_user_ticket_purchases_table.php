<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Покупки билетов пользователями
 * Партиционированная таблица для хранения истории покупок билетов
 * Партиционирование по лотереям (lottery_id)
 */
final class CreateUserTicketPurchasesTable extends AbstractMigration
{
    public function change(): void
    {
        // Создаем партиционированную таблицу сразу через SQL
        $this->execute("
            CREATE TABLE user_ticket_purchases (
                id BIGSERIAL,
                user_id BIGINT NOT NULL,
                ticket_id INTEGER NOT NULL,
                lottery_id INTEGER NOT NULL,
                basket_id INTEGER NOT NULL,
                purchase_amount BIGINT NOT NULL,
                purchase_currency_id INTEGER NOT NULL,

                tickets_exported_at TIMESTAMP NULL,
                purchased_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (lottery_id, id)
            ) PARTITION BY RANGE (lottery_id);
        ");

        // Добавляем комментарии
        $this->execute("COMMENT ON TABLE user_ticket_purchases IS 'Покупки билетов пользователями';");
        $this->execute("COMMENT ON COLUMN user_ticket_purchases.id IS 'Глобально уникальный ID (автоинкремент)';");
        $this->execute("COMMENT ON COLUMN user_ticket_purchases.user_id IS 'ID пользователя-покупателя';");
        $this->execute("COMMENT ON COLUMN user_ticket_purchases.ticket_id IS 'ID билета в партиционированной таблице';");
        $this->execute("COMMENT ON COLUMN user_ticket_purchases.lottery_id IS 'ID лотереи для денормализации';");
        $this->execute("COMMENT ON COLUMN user_ticket_purchases.basket_id IS 'ID корзины покупок';");
        $this->execute("COMMENT ON COLUMN user_ticket_purchases.purchase_amount IS 'Сумма покупки в минимальных единицах валюты';");
        $this->execute("COMMENT ON COLUMN user_ticket_purchases.purchase_currency_id IS 'Валюта покупки (деньги/монеты)';");

        $this->execute("COMMENT ON COLUMN user_ticket_purchases.tickets_exported_at IS 'Дата отправки билета в Kafka';");
        $this->execute("COMMENT ON COLUMN user_ticket_purchases.purchased_at IS 'Дата и время покупки билета';");

        // Создаем индексы
        $this->execute("CREATE INDEX idx_user_ticket_purchases_user_id ON user_ticket_purchases (user_id);");
        $this->execute("CREATE INDEX idx_user_ticket_purchases_lottery_id ON user_ticket_purchases (lottery_id);");
        $this->execute("CREATE INDEX idx_user_ticket_purchases_basket_id ON user_ticket_purchases (basket_id);");
        $this->execute("CREATE INDEX idx_user_ticket_purchases_purchased_at ON user_ticket_purchases (purchased_at);");
        $this->execute("CREATE INDEX idx_user_ticket_purchases_ticket_id ON user_ticket_purchases (ticket_id);");

        $this->execute("CREATE INDEX idx_user_ticket_purchases_tickets_exported_at ON user_ticket_purchases (tickets_exported_at);");
        $this->execute("CREATE INDEX idx_user_ticket_purchases_lottery_tickets_exported ON user_ticket_purchases (lottery_id, tickets_exported_at);");
        $this->execute("CREATE INDEX idx_user_ticket_purchases_user_lottery ON user_ticket_purchases (user_id, lottery_id);");

        // Уникальный индекс для предотвращения дублирования - один билет из одной лотереи может быть куплен только один раз
        $this->execute("CREATE UNIQUE INDEX idx_user_ticket_purchases_unique_ticket_lottery ON user_ticket_purchases (ticket_id, lottery_id);");

        // Добавляем внешние ключи
        $this->execute("ALTER TABLE user_ticket_purchases ADD CONSTRAINT fk_user_ticket_purchases_lottery_id FOREIGN KEY (lottery_id) REFERENCES lottery_numbers(id) ON DELETE RESTRICT ON UPDATE RESTRICT;");
        $this->execute("ALTER TABLE user_ticket_purchases ADD CONSTRAINT fk_user_ticket_purchases_basket_id FOREIGN KEY (basket_id) REFERENCES user_baskets(id) ON DELETE RESTRICT ON UPDATE RESTRICT;");
        $this->execute("ALTER TABLE user_ticket_purchases ADD CONSTRAINT fk_user_ticket_purchases_purchase_currency_id FOREIGN KEY (purchase_currency_id) REFERENCES payment_currencies(id) ON DELETE RESTRICT ON UPDATE RESTRICT;");
    }
}
