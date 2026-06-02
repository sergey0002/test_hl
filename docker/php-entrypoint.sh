#!/bin/sh
set -e

if [ -d /var/www/html/laravel-app/storage ]; then
    chown -R www-data:www-data /var/www/html/laravel-app/storage /var/www/html/laravel-app/bootstrap/cache 2>/dev/null || true
fi

exec docker-php-entrypoint "$@"
