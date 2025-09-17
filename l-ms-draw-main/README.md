# MS Draw Service

Микросервис для проведения розыгрышей лотерей.

## Основные функции

- Получение расписаний лотерей через Kafka
- Получение конфигурации призовых мест
- Получение билетов из типизированных топиков (daily_fixed, daily_dynamic, jackpot, supertour)
- Проведение розыгрышей и отправка результатов
- Партиционирование билетов по lottery_id для каждого типа лотереи

## Быстрый старт

### Копирование env
```bash
cp -i .env.example .env 
```

### Запуск приложения
```bash 
make up
```

### Остановка приложения
```bash 
make down
```

### Выполнение миграций
```bash 
docker exec -it ms-draw-service composer phinx-migrate-up
```

## Консьюмеры

### Консьюмеры билетов

Для каждого типа лотереи есть отдельный консьюмер:

```bash
# Консьюмер билетов daily_fixed лотерей
docker exec -it ms-draw-service php webman consume-tickets:daily-fixed

# Консьюмер билетов daily_dynamic лотерей  
docker exec -it ms-draw-service php webman consume-tickets:daily-dynamic

# Консьюмер билетов jackpot лотерей
docker exec -it ms-draw-service php webman consume-tickets:jackpot

# Консьюмер билетов supertour лотерей
docker exec -it ms-draw-service php webman consume-tickets:supertour
```

### Системные консьюмеры

```bash
# Консьюмер расписаний лотерей
docker exec -it ms-draw-service php webman consume-schedules:run

# Консьюмер конфигурации розыгрышей  
docker exec -it ms-draw-service php webman consume-draw-config:run
```

### Команды розыгрыша

```bash
# Проведение розыгрышей лотерей
docker exec -it ms-draw-service php webman draw:lotteries
```

## Архитектура

### Топики Kafka

**Входящие:**
- `daily_fixed_tickets_v1` - билеты ежедневных лотерей с фиксированным призом
- `daily_dynamic_tickets_v1` - билеты ежедневных лотерей с динамическим призом  
- `jackpot_tickets_v1` - билеты джекпот лотерей
- `supertour_tickets_v1` - билеты супертур лотерей
- `lottery_schedules_v1` - расписания лотерей
- `lottery_draw_config_v1` - конфигурация розыгрышей

**Исходящие:**
- `lottery_draw_results_v1` - результаты розыгрышей

**DLQ (Dead Letter Queue):**
- `dlq_tickets_v1` - неудачные сообщения с билетами
- `dlq_schedules_v1` - неудачные сообщения с расписаниями

### Структура БД

**Основная таблица:**
- `lottery_numbers` - все лотереи всех типов

**Партиционированные таблицы билетов:**
- `daily_fixed_tickets` - билеты ежедневных лотерей с фиксированным призом
- `daily_dynamic_tickets` - билеты ежедневных лотерей с динамическим призом
- `jackpot_tickets` - билеты джекпот лотерей
- `supertour_tickets` - билеты супертур лотерей

Каждая таблица билетов автоматически создает партиции по `lottery_id`.

### Логирование

Каждый консьюмер пишет логи в отдельный файл:
- `runtime/logs/command_consume_daily_fixed_tickets.log`
- `runtime/logs/command_consume_daily_dynamic_tickets.log`
- `runtime/logs/command_consume_jackpot_tickets.log`
- `runtime/logs/command_consume_supertour_tickets.log`
- `runtime/logs/command_consume_schedules.log`
- `runtime/logs/command_consume_draw_config.log`

## Локальная разработка

### Предварительные требования

Перед запуском убедитесь, что у вас запущена общая инфраструктура:

```bash
cd ../shared-infra
docker-compose up -d
```

### Запуск микросервиса

1. Клонируйте репозиторий:
```bash
git clone <url-репозитория> ms-draw-service
cd ms-draw-service
```

2. Создайте .env файл:
```bash
cp .env.example .env
```

3. Запустите приложение:
```bash
docker-compose up -d
```

Приложение будет доступно по адресу: http://localhost:8086

### База данных

Используется PostgreSQL база `ms_draw` из общей инфраструктуры.
База создается автоматически при запуске shared-infra.

### Автоматические процессы

Микросервис автоматически запускает следующие процессы:

### 🎲 Процесс розыгрышей (`DrawLotteryProcess`)
- **Расписание**: каждые 5 минут (`*/5 * * * *`)
- **Функция**: проверяет готовые к розыгрышу лотереи и проводит их
- **Лог**: `runtime/logs/process_draw_lottery.log`

### 📅 Процесс консьюмера расписаний (`ConsumeSchedulesProcess`)
- **Тип**: постоянно работающий процесс
- **Функция**: получает расписания лотерей из Kafka топика `lottery_schedules_v1`
- **Автоперезапуск**: через 10 секунд при нормальном завершении, через 30 секунд при ошибке
- **Лог**: `runtime/logs/process_consume_schedules.log`

### 🏆 Процесс консьюмера конфигурации (`ConsumeDrawConfigProcess`)
- **Тип**: постоянно работающий процесс
- **Функция**: получает конфигурацию призовых мест из Kafka топика `lottery_draw_config_v1`
- **Автоперезапуск**: через 10 секунд при нормальном завершении, через 30 секунд при ошибке
- **Лог**: `runtime/logs/process_consume_draw_config.log`

### 🎟️ Процесс консьюмеров билетов (`ConsumeTicketsProcess`)
- **Тип**: постоянно работающие процессы (4 консьюмера)
- **Функция**: получает билеты всех типов из соответствующих Kafka топиков:
  - `daily_fixed_tickets_v1`
  - `daily_dynamic_tickets_v1`
  - `jackpot_tickets_v1`
  - `supertour_tickets_v1`
- **Автоперезапуск**: через 10 секунд при нормальном завершении, через 30 секунд при ошибке
- **Лог**: `runtime/logs/process_consume_tickets.log`

## Мониторинг

### Просмотр логов процессов:
```bash
# Лог процесса розыгрышей
docker exec -it ms-draw-service tail -f runtime/logs/process_draw_lottery.log

# Лог процесса консьюмера расписаний
docker exec -it ms-draw-service tail -f runtime/logs/process_consume_schedules.log

# Лог процесса консьюмера конфигурации
docker exec -it ms-draw-service tail -f runtime/logs/process_consume_draw_config.log

# Лог процесса консьюмеров билетов
docker exec -it ms-draw-service tail -f runtime/logs/process_consume_tickets.log

# Просмотр всех логов процессов
docker exec -it ms-draw-service tail -f runtime/logs/process_*.log
```

### Просмотр логов консьюмеров:
```bash
# Логи отдельных консьюмеров билетов
docker exec -it ms-draw-service tail -f runtime/logs/command_consume_daily_fixed_tickets.log
docker exec -it ms-draw-service tail -f runtime/logs/command_consume_daily_dynamic_tickets.log
docker exec -it ms-draw-service tail -f runtime/logs/command_consume_jackpot_tickets.log
docker exec -it ms-draw-service tail -f runtime/logs/command_consume_supertour_tickets.log

# Логи системных консьюмеров
docker exec -it ms-draw-service tail -f runtime/logs/command_consume_schedules.log
docker exec -it ms-draw-service tail -f runtime/logs/command_consume_draw_config.log

# Просмотр всех логов консьюмеров
docker exec -it ms-draw-service tail -f runtime/logs/command_*.log
```

### Проверка статуса процессов:
```bash
# Статус всех процессов
docker exec -it ms-draw-service php webman status

# Перезапуск всех процессов
docker exec -it ms-draw-service php webman restart
```
