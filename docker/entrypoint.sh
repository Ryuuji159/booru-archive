#!/bin/sh

set -e

if [ "$(id -u)" = "0" ]; then
    chown -R www-data:www-data /app/storage /app/bootstrap/cache
fi

mkdir -p /app/storage/database \
         /app/storage/framework/views \
         /app/storage/framework/cache/data \
         /app/storage/framework/sessions \
         /app/storage/app/media \
         /app/bootstrap/cache

touch /app/storage/database/database.sqlite

if [ "${RUN_MIGRATIONS:-1}" = "1" ]; then
    php artisan migrate --force
fi

exec /usr/local/bin/docker-php-entrypoint "$@"
