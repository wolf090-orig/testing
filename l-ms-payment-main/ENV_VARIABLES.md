
### Интеграции с микросервисами

MS_TICKET_MANAGER_API_TOKEN=your_incoming_token_from_ticket_manager

### FPGate PayIn (пополнение) - LotteryT каскад вход

FPGATE_PAYIN_BASE_URL=https://app.juppiter.tech
FPGATE_PAYIN_TOKEN=37:31:32:31:39:38:38
FPGATE_PAYIN_SECRET=test1

### FPGate PayOut (вывод) - LotteryT PS выход

FPGATE_PAYOUT_BASE_URL=https://app.juppiter.tech
FPGATE_PAYOUT_TOKEN=34:34:39:34:36:34:30
FPGATE_PAYOUT_SECRET=test2

## Опциональные переменные (с умолчаниями)

### Общие настройки

PAYMENT_DEFAULT_GATEWAY=fpgate
FPGATE_TIMEOUT_SECONDS=30


### Лимиты платежей (в копейках)
PAYIN_MIN_AMOUNT=10000
PAYIN_MAX_AMOUNT=10000000
PAYOUT_MIN_AMOUNT=50000
PAYOUT_MAX_AMOUNT=5000000

### Таймауты
PAYMENT_EXPIRY_MINUTES=15

KAFKA_TOPIC_PAYMENT_STATUS_V1=payment_status_v1
