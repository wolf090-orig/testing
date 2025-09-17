<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Таблица победителей лотерей
 * Партиционированная таблица для хранения информации о выигрышных билетах
 * Партиционирование по лотереям (lottery_id)
 */
final class CreateWinnerTicketsTable extends AbstractMigration
{
    public function change(): void
    {
        // Создаем партиционированную таблицу сразу через SQL
        $this->execute("
            CREATE TABLE winner_tickets (
                id BIGSERIAL,
                user_ticket_purchase_id BIGINT NOT NULL,
                lottery_id INTEGER NOT NULL,
                user_id BIGINT NOT NULL,
                winner_position INTEGER NOT NULL,
                payout_amount BIGINT NULL,
                payout_currency_id INTEGER NULL,
                is_paid BOOLEAN DEFAULT FALSE,
                paid_at TIMESTAMP NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (lottery_id, id)
            ) PARTITION BY RANGE (lottery_id);
        ");

        // Добавляем комментарии
        $this->execute("COMMENT ON TABLE winner_tickets IS 'Таблица победителей лотерей';");
        $this->execute("COMMENT ON COLUMN winner_tickets.id IS 'Глобально уникальный ID (автоинкремент)';");
        $this->execute("COMMENT ON COLUMN winner_tickets.user_ticket_purchase_id IS 'ID покупки билета из user_ticket_purchases';");
        $this->execute("COMMENT ON COLUMN winner_tickets.lottery_id IS 'ID лотереи для денормализации';");
        $this->execute("COMMENT ON COLUMN winner_tickets.user_id IS 'ID пользователя для денормализации';");
        $this->execute("COMMENT ON COLUMN winner_tickets.winner_position IS 'Позиция выигрыша (1-е место, 2-е место и т.д.)';");
        $this->execute("COMMENT ON COLUMN winner_tickets.payout_amount IS 'Сумма выплаты приза (NULL для товаров)';");
        $this->execute("COMMENT ON COLUMN winner_tickets.payout_currency_id IS 'Валюта выплаты приза (NULL для товаров)';");
        $this->execute("COMMENT ON COLUMN winner_tickets.is_paid IS 'Выплачен ли приз пользователю';");
        $this->execute("COMMENT ON COLUMN winner_tickets.paid_at IS 'Дата и время выплаты приза';");

        // Создаем индексы
        $this->execute("CREATE INDEX idx_winner_tickets_user_ticket_purchase_id ON winner_tickets (user_ticket_purchase_id);");
        $this->execute("CREATE INDEX idx_winner_tickets_lottery_id ON winner_tickets (lottery_id);");
        $this->execute("CREATE INDEX idx_winner_tickets_user_id ON winner_tickets (user_id);");
        $this->execute("CREATE INDEX idx_winner_tickets_winner_position ON winner_tickets (winner_position);");
        $this->execute("CREATE INDEX idx_winner_tickets_is_paid ON winner_tickets (is_paid);");
        $this->execute("CREATE INDEX idx_winner_tickets_payout_amount ON winner_tickets (payout_amount);");
        $this->execute("CREATE INDEX idx_winner_tickets_user_lottery ON winner_tickets (user_id, lottery_id);");

        // Уникальный индекс для предотвращения дублирования - один билет не может быть выигрышным дважды
        $this->execute("CREATE UNIQUE INDEX idx_winner_tickets_unique_purchase ON winner_tickets (user_ticket_purchase_id, lottery_id);");

        // Добавляем внешние ключи
        $this->execute("ALTER TABLE winner_tickets ADD CONSTRAINT fk_winner_tickets_lottery_id FOREIGN KEY (lottery_id) REFERENCES lottery_numbers(id) ON DELETE RESTRICT ON UPDATE RESTRICT;");
        $this->execute("ALTER TABLE winner_tickets ADD CONSTRAINT fk_winner_tickets_user_ticket_purchase FOREIGN KEY (lottery_id, user_ticket_purchase_id) REFERENCES user_ticket_purchases(lottery_id, id) ON DELETE RESTRICT ON UPDATE RESTRICT;");
        $this->execute("ALTER TABLE winner_tickets ADD CONSTRAINT fk_winner_tickets_payout_currency_id FOREIGN KEY (payout_currency_id) REFERENCES payment_currencies(id) ON DELETE RESTRICT ON UPDATE RESTRICT;");
    }
}
