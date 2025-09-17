Readme

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
docker exec -it ms-payment composer phinx-migrate-up
```

#------------------Локальная разработка------------------#

## Предварительные требования

Перед запуском убедитесь, что у вас запущена общая инфраструктура из репозитория shared-infra:

```bash
cd ../shared-infra
docker-compose up -d
```

## Запуск микросервиса

1. Клонируйте репозиторий:

```bash
git clone <url-репозитория> ms-payment
cd ms-payment
```

2. Создайте .env файл на основе .env.example:

```bash
cp .env.example .env
```

3. Запустите приложение:

```bash
docker-compose up -d
```

Приложение будет доступно по адресу: http://localhost:8085 (или порт, указанный в .env)

## Разработка

Код приложения привязан через volumes к docker-контейнеру, поэтому изменения в коде будут применяться автоматически без необходимости перезапуска сервиса. Это значительно упрощает процесс разработки и отладки.

## База данных

Приложение использует базу данных PostgreSQL `ms_payment` из общей инфраструктуры. База данных создается автоматически при первом запуске общей инфраструктуры (shared-infra).

Если вам нужно создать базу данных вручную, вы можете выполнить:

```bash
docker exec -it nlu-postgres psql -U postgres -d postgres -c "SELECT create_database_if_not_exists('ms_payment');"
```


### Выполнение миграций
```bash 
docker exec -it ms-payment composer phinx-migrate-up
```

### Выполнение seed
```bash 
docker exec -it ms-payment composer phinx-seed-up
```