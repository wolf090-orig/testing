# Тестовые сценарии FakePaymentGateway

FakePaymentGateway автоматически определяет режим тестирования на основе входящих данных:

## 🧪 Тестовые случаи

### 1. ❌ MODE_NO_CARDS (ошибка 1004)
**Условие:** Сумма платежа = 1000 рублей  
**Приоритет:** Высший (проверяется первым)

```bash
curl --location 'http://localhost:8087/api/v1/payments/payin' \
--header 'Authorization: Bearer secret_123' \
--header 'Content-Type: application/json' \
--data '{
    "internal_order_id": "test_no_cards",
    "user_id": 1,
    "amount": 1000,
    "currency": "RUB",
    "payment_method": "card"
}'
```

**Ответ:**
```json
{
  "message": "Произошла ошибка",
  "status": "error",
  "errors": {
    "code": "1004",
    "details": "No Cards / no phonenumbers"
  }
}
```

### 2. ❌ MODE_DUPLICATE (ошибка 1011)
**Условие:** `order_id` содержит "basket_0"  
**Приоритет:** Средний (если сумма ≠ 1000)

```bash
curl --location 'http://localhost:8087/api/v1/payments/payin' \
--header 'Authorization: Bearer secret_123' \
--header 'Content-Type: application/json' \
--data '{
    "internal_order_id": "basket_0_test",
    "user_id": 1,
    "amount": 500,
    "currency": "RUB",
    "payment_method": "card"
}'
```

**Ответ:**
```json
{
  "message": "Произошла ошибка", 
  "status": "error",
  "errors": {
    "code": "1011",
    "details": "Transaction with this order_id already exists"
  }
}
```

### 3. ✅ MODE_SUCCESS (успешный ответ)
**Условие:** Все остальные случаи  
**Приоритет:** Низший (по умолчанию)

```bash
curl --location 'http://localhost:8087/api/v1/payments/payin' \
--header 'Authorization: Bearer secret_123' \
--header 'Content-Type: application/json' \
--data '{
    "internal_order_id": "basket_test_success",
    "user_id": 1,
    "amount": 500,
    "currency": "RUB", 
    "payment_method": "card"
}'
```

**Ответ:**
```json
{
  "message": "Платеж создан успешно",
  "status": "success",
  "data": {
    "internal_order_id": "basket_test_success",
    "external_transaction_id": "fake_payin_...",
    "payment_details": {
      "name": "Тестовый Пользователь",
      "card": "2200150000000000", 
      "bank": "ТЕСТОВЫЙ БАНК"
    },
    "status": {"type": "processing"}
  }
}
```

## 🎯 Приоритеты проверок

1. **Сумма 1000** → MODE_NO_CARDS (даже если есть basket_0)
2. **basket_0 в order_id** → MODE_DUPLICATE (если сумма ≠ 1000)
3. **Остальное** → MODE_SUCCESS

## 📝 Примеры комбинаций

- `amount=1000` + `order_id=basket_0_test` → **MODE_NO_CARDS** (приоритет у суммы)
- `amount=500` + `order_id=basket_0_test` → **MODE_DUPLICATE**
- `amount=500` + `order_id=regular_order` → **MODE_SUCCESS**
- `amount=1000` + `order_id=regular_order` → **MODE_NO_CARDS**
