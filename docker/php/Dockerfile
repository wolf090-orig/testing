FROM php:8.4.2-fpm-alpine

# Установка системных зависимостей
RUN apk add --no-cache \
    postgresql-dev \
    zip \
    unzip \
    git \
    curl

# Установка PHP расширений
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    opcache

# Установка Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Настройка рабочей директории
WORKDIR /var/www/html

# Копирование composer файлов
COPY app/composer.json app/composer.lock* ./

# Установка зависимостей
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Копирование приложения
COPY app/ .

# Настройка прав доступа
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Настройка PHP-FPM
RUN echo "listen = 0.0.0.0:9000" >> /usr/local/etc/php-fpm.d/zz-docker.conf

EXPOSE 9000

CMD ["php-fpm"]