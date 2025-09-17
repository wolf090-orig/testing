# 🚀 ИНСТРУКЦИЯ ПО ЗАПУСКУ СИСТЕМЫ "LOTO"

## 📋 ОГЛАВЛЕНИЕ
1. [Предварительные требования](#-предварительные-требования)
2. [Быстрый запуск](#-быстрый-запуск)  
3. [Пошаговый запуск](#-пошаговый-запуск)
4. [Проверка работоспособности](#-проверка-работоспособности)
5. [Доступ к интерфейсам](#-доступ-к-интерфейсам)
6. [Полезные команды](#-полезные-команды)
7. [Устранение проблем](#-устранение-проблем)

---

## 📦 ПРЕДВАРИТЕЛЬНЫЕ ТРЕБОВАНИЯ

### Обязательно:
- **Docker Desktop** 20.0+ ([Скачать](https://www.docker.com/products/docker-desktop/))
- **Docker Compose** 2.0+ (входит в Docker Desktop)
- **Git** для клонирования репозитория
- **8GB RAM** минимум (рекомендуется 16GB)

### Для разработки:
- **Node.js 18+** ([Скачать](https://nodejs.org/))
- **PHP 8.3+** (опционально, для локальной разработки)
- **Make** утилита (Linux/macOS) или альтернатива для Windows

### Проверка установки:
```bash
docker --version        # Docker version 20.0+
docker-compose --version # Docker Compose version 2.0+
node --version          # v18.0+
git --version          # Любая современная версия
```

---

## ⚡ БЫСТРЫЙ ЗАПУСК

> 🕒 **Время запуска**: ~5-10 минут (первый раз может занять до 15 минут)

```bash
# 1. Клонирование репозитория
git clone <repository-url> loto
cd loto

# 2. Запуск инфраструктуры (PostgreSQL, Redis, Kafka)
cd l-shared-infra
docker-compose up -d

# 3. Ожидание готовности инфраструктуры (~2-3 минуты)
docker logs -f nlu-db-init  # Дождитесь "Database initialization completed"

# 4. Запуск бэкенд сервисов
cd ../l-ms-ticket-manager && make up
cd ../l-ms-user && make up

# 5. Запуск фронтенда
cd ../l-ms-telegram-app/app
npm install
npm run dev

# 6. Готово! 🎉
# Фронтенд: http://localhost:5555
# Kafka UI: http://localhost:81
```

---

## 🔧 ПОШАГОВЫЙ ЗАПУСК

### ШАГ 1: Подготовка окружения

```bash
# Клонирование проекта
git clone <repository-url> loto
cd loto

# Создание общей Docker сети (если не существует)
docker network create nlu-shared-network 2>/dev/null || true
```

### ШАГ 2: Запуск общей инфраструктуры

```bash
cd l-shared-infra

# Запуск инфраструктурных сервисов
docker-compose up -d

# Проверка статуса
docker-compose ps
```

**Ожидаемый результат:**
```
NAME            IMAGE                    STATUS
nlu-postgres    postgres:latest          Up
nlu-redis       redis:latest             Up  
nlu-kafka       bitnami/kafka:3.4        Up
nlu-kafka-ui    provectuslabs/kafka-ui   Up
nlu-nginx       nginx:latest             Up
```

### ШАГ 3: Инициализация баз данных

```bash
# Ожидание создания баз данных
docker logs -f nlu-db-init

# Дождитесь сообщения:
# "✅ Database initialization completed successfully!"
```

**Созданные базы данных:**
- `ms_ticket_manager` - для сервиса билетов
- `ms_user` - для пользовательского сервиса
- `ms_profile` - резерв
- `ms_auth` - резерв  
- `ms_draw` - резерв
- `ms_notification` - резерв

### ШАГ 4: Запуск Ticket Manager Service

```bash
cd ../l-ms-ticket-manager

# Копирование .env файла (если не существует)
cp .env.example .env

# Запуск сервиса
make up
# или docker-compose up -d

# Выполнение миграций
docker exec -it ms-ticket-manager composer phinx-migrate-up

# Запуск сидеров (начальные данные)
docker exec -it ms-ticket-manager composer phinx-seed-up
```

**Проверка запуска:**
```bash
# Проверка логов
docker logs ms-ticket-manager

# Проверка здоровья сервиса
curl http://localhost:8084/api/health
```

### ШАГ 5: Запуск User Service

```bash
cd ../l-ms-user

# Копирование .env файла
cp .env.example .env

# Запуск сервиса
make up
# или docker-compose up -d

# Выполнение миграций  
docker exec -it ms-user composer phinx-migrate-up

# Запуск сидеров
docker exec -it ms-user composer phinx-seed-up
```

**Проверка запуска:**
```bash
# Проверка логов
docker logs ms-user

# Проверка здоровья сервиса
curl http://localhost:8085/api/health
```

### ШАГ 6: Запуск Frontend (Telegram Mini App)

```bash
cd ../l-ms-telegram-app/app

# Установка зависимостей
npm install

# Создание .env файла для разработки
cat > .env.local << EOF
VITE_API_URL=http://localhost:8088/api/v1
VITE_DEBUG=true
VITE_API_DEBUG=true
VITE_APP_NAME=Loto Development
VITE_DEV_USER_OVERRIDE=true
VITE_DEV_USER_ID=12345678
VITE_DEV_USER_FIRST_NAME=Test
VITE_DEV_USER_LAST_NAME=User
VITE_DEV_SECRET_KEY=dev_secret_only
EOF

# Запуск в режиме разработки
npm run dev

# Альтернативно - запуск с доступом по сети
npm run dev-host
```

**Ожидаемый результат:**
```
  ➜  Local:   http://localhost:5555/
  ➜  Network: http://192.168.x.x:5555/
  ➜  press h to show help
```

---

## ✅ ПРОВЕРКА РАБОТОСПОСОБНОСТИ

### 1. Проверка статуса всех контейнеров

```bash
# Из корневой папки проекта
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"
```

**Ожидаемый результат:**
```
NAMES                STATUS        PORTS
ms-ticket-manager    Up 2 minutes  0.0.0.0:8084->8088/tcp
ms-user              Up 2 minutes  0.0.0.0:8085->8088/tcp  
nlu-postgres         Up 5 minutes  0.0.0.0:54320->5432/tcp
nlu-redis            Up 5 minutes  0.0.0.0:63790->6379/tcp
nlu-kafka            Up 5 minutes
nlu-kafka-ui         Up 5 minutes  0.0.0.0:81->8080/tcp
nlu-nginx            Up 5 minutes  0.0.0.0:8088->8088/tcp
```

### 2. Проверка API endpoints

```bash
# Health checks
curl -s http://localhost:8084/api/health | jq
curl -s http://localhost:8085/api/health | jq

# Получение списка лотерей (может потребовать авторизации)
curl -s http://localhost:8084/api/v1/lotteries | jq
```

### 3. Проверка баз данных

```bash  
# Подключение к PostgreSQL
docker exec -it nlu-postgres psql -U postgres -l

# Проверка таблиц в базе билетов
docker exec -it nlu-postgres psql -U postgres -d ms_ticket_manager -c "\dt"
```

### 4. Проверка Kafka топиков

```bash
# Список топиков
docker exec -it nlu-kafka kafka-topics.sh --bootstrap-server localhost:9092 --list

# Или через UI: http://localhost:81
```

---

## 🌐 ДОСТУП К ИНТЕРФЕЙСАМ

| Сервис | URL | Описание |
|--------|-----|----------|
| **Frontend** | http://localhost:5555 | Telegram Mini App |
| **API Gateway** | http://localhost:8088 | Nginx прокси |  
| **Ticket Manager** | http://localhost:8084 | Прямой доступ к API |
| **User Service** | http://localhost:8085 | Прямой доступ к API |
| **Kafka UI** | http://localhost:81 | Мониторинг Kafka |
| **PostgreSQL** | localhost:54320 | База данных |
| **Redis** | localhost:63790 | Кеш |

### Подключение к PostgreSQL:
```bash
# Через Docker
docker exec -it nlu-postgres psql -U postgres

# Внешнее подключение
Host: localhost
Port: 54320  
User: postgres
Password: password
```

### Подключение к Redis:
```bash
# Через Docker  
docker exec -it nlu-redis redis-cli

# Внешнее подключение
Host: localhost
Port: 63790
```

---

## 🛠️ ПОЛЕЗНЫЕ КОМАНДЫ

### Управление Docker контейнерами

```bash
# Остановка всех сервисов
cd l-shared-infra && docker-compose down
cd ../l-ms-ticket-manager && make down
cd ../l-ms-user && make down

# Перезапуск конкретного сервиса
docker restart ms-ticket-manager

# Логи сервисов
docker logs -f ms-ticket-manager
docker logs -f ms-user  
docker logs -f nlu-postgres
```

### Работа с базами данных

```bash
# Выполнение миграций
docker exec -it ms-ticket-manager composer phinx-migrate-up
docker exec -it ms-user composer phinx-migrate-up

# Откат миграций
docker exec -it ms-ticket-manager composer phinx-migrate-down

# Запуск сидеров
docker exec -it ms-ticket-manager composer phinx-seed-up
docker exec -it ms-user composer phinx-seed-up
```

### Команды для лотерей (Ticket Manager)

```bash  
# Генерация лотерей на 7 дней
docker exec -it ms-ticket-manager php webman lottery-numbers:generate --days=7

# Создание билетов для конкретной даты
docker exec -it ms-ticket-manager php webman lottery-tickets:create 2025-04-05 2025-04-05

# Экспорт активных лотерей
docker exec -it ms-ticket-manager php webman export_active_lotteries:run

# Расчет победителей
docker exec -it ms-ticket-manager php webman calculate_lottery_winners:run
```

### Работа с Frontend

```bash
cd l-ms-telegram-app/app

# Режим разработки
npm run dev

# Режим разработки с сетевым доступом
npm run dev-host

# Сборка для продакшена
npm run build

# Проверка типов
npm run type-check
```

---

## 🩺 УСТРАНЕНИЕ ПРОБЛЕМ

### Проблема 1: Не запускается PostgreSQL

**Симптомы:**
```
nlu-postgres exited with code 1
```

**Решение:**
```bash
# Остановить все контейнеры
docker-compose down -v

# Очистить volumes
docker volume prune -f

# Запустить заново
docker-compose up -d
```

### Проблема 2: Ошибка подключения к базе данных

**Симптомы:**
```
SQLSTATE[08006] [7] could not connect to server
```

**Решение:**
```bash
# Проверить статус PostgreSQL
docker logs nlu-postgres

# Дождаться инициализации баз данных
docker logs -f nlu-db-init

# Проверить сеть
docker network inspect nlu-shared-network
```

### Проблема 3: Kafka не отвечает

**Симптомы:**
```
Failed to resolve 'nlu-kafka:9094'
```

**Решение:**
```bash
# Перезапуск Kafka
docker restart nlu-kafka

# Проверка топиков
docker exec -it nlu-kafka kafka-topics.sh --bootstrap-server localhost:9092 --list

# Пересоздание топиков
docker restart nlu-kafka-init
```

### Проблема 4: Frontend не подключается к API

**Симптомы:**
```
Network Error / CORS Error
```

**Решение:**
```bash
# Проверить Nginx конфигурацию
docker logs nlu-nginx

# Проверить доступность API
curl http://localhost:8088/api/health

# Перезапустить Nginx
docker restart nlu-nginx
```

### Проблема 5: Нет прав доступа к файлам

**Симптомы:**
```
Permission denied
```

**Решение:**
```bash
# Linux/macOS
sudo chown -R $USER:$USER .
chmod -R 755 .

# Windows (PowerShell as Administrator)
takeown /r /d y /f .
```

### Полная перезагрузка системы

Если ничего не помогает:

```bash
# 1. Остановка всех контейнеров
docker stop $(docker ps -aq)

# 2. Удаление всех контейнеров
docker rm $(docker ps -aq)

# 3. Удаление volumes
docker volume prune -f

# 4. Удаление сети
docker network rm nlu-shared-network 2>/dev/null || true

# 5. Повторный запуск по инструкции
```

---

## 📞 ПОЛУЧЕНИЕ ПОМОЩИ

Если проблемы продолжаются:

1. **Проверьте логи:** `docker logs <container-name>`
2. **Проверьте ресурсы:** Достаточно ли RAM/CPU?
3. **Проверьте порты:** Не заняты ли порты другими приложениями?
4. **Создайте Issue** с описанием проблемы и логами

### Полезные команды для диагностики:

```bash
# Использование ресурсов
docker stats

# Проверка занятых портов
netstat -tulpn | grep -E ':(54320|63790|8084|8085|8088|81|5555)'

# Состояние системы
docker system df
docker system events
```

---

## ✅ ГОТОВНОСТЬ К РАБОТЕ

После успешного запуска у вас должно быть:

- ✅ **4 работающих backend контейнера**
- ✅ **3 инфраструктурных контейнера** 
- ✅ **Frontend доступен** на http://localhost:5555
- ✅ **API отвечает** на health checks
- ✅ **Kafka UI показывает топики** на http://localhost:81
- ✅ **Базы данных содержат таблицы** со стартовыми данными

🎉 **Поздравляем! Система Loto готова к работе!**

---

<div align="center">
  <strong>📞 Вопросы? Создайте Issue или обратитесь к документации каждого сервиса</strong>
</div> 