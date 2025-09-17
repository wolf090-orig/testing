# Multi-stage build для оптимизации размера образа

# Этап 1: Сборка приложения
FROM node:18-alpine AS builder

# Установка рабочей директории
WORKDIR /app

# Копирование файлов зависимостей
COPY package*.json ./

# Установка зависимостей
RUN npm ci --only=production

# Копирование исходного кода
COPY . .

# Сборка приложения для продакшена
RUN npm run build

# Этап 2: Настройка Nginx для обслуживания статических файлов
FROM nginx:alpine AS production

# Копирование собранного приложения из предыдущего этапа
COPY --from=builder /app/dist /usr/share/nginx/html

# Копирование конфигурации Nginx
COPY nginx.conf /etc/nginx/nginx.conf

# Открытие порта 80
EXPOSE 80

# Запуск Nginx
CMD ["nginx", "-g", "daemon off;"]