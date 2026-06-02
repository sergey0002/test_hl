FROM php:8.2-fpm

# Установка необходимых системных зависимостей для PHP-расширений.
RUN apt-get update && apt-get install -y \
    git curl zip unzip libpq-dev \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo_pgsql pdo pcntl
RUN pecl install redis && docker-php-ext-enable redis

# Composer нужен для возможного расширения проекта (тесты, статанализ).
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . /var/www/html
COPY ./php/99-custom.ini /usr/local/etc/php/conf.d/99-custom.ini
COPY ./docker/php-entrypoint.sh /usr/local/bin/php-entrypoint.sh
RUN chmod +x /usr/local/bin/php-entrypoint.sh

RUN mkdir -p /var/www/html/storage/exports \
    /var/www/html/laravel-app/storage/framework/cache \
    /var/www/html/laravel-app/storage/framework/sessions \
    /var/www/html/laravel-app/storage/framework/views \
    /var/www/html/laravel-app/storage/logs \
    /var/www/html/laravel-app/bootstrap/cache \
    && chown -R www-data:www-data /var/www/html

ENTRYPOINT ["php-entrypoint.sh"]
CMD ["php-fpm"]

EXPOSE 9000