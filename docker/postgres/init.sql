-- Создание таблицы заявок на займы
CREATE TABLE loan_requests (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL,
    amount INTEGER NOT NULL CHECK (amount > 0),
    term INTEGER NOT NULL CHECK (term > 0),
    status VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'approved', 'declined')),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Создание индексов для оптимизации запросов
CREATE INDEX idx_loan_requests_user_id ON loan_requests(user_id);
CREATE INDEX idx_loan_requests_status ON loan_requests(status);
CREATE INDEX idx_loan_requests_created_at ON loan_requests(created_at DESC);
CREATE INDEX idx_loan_requests_user_status ON loan_requests(user_id, status);

-- Создание функции для автоматического обновления updated_at
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ language 'plpgsql';

-- Создание триггера для автоматического обновления updated_at
CREATE TRIGGER update_loan_requests_updated_at
    BEFORE UPDATE ON loan_requests
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

-- Вставка тестовых данных
INSERT INTO loan_requests (user_id, amount, term, status) VALUES
(1, 5000, 30, 'pending'),
(2, 10000, 60, 'pending'),
(3, 3000, 15, 'approved'),
(4, 7500, 45, 'declined');