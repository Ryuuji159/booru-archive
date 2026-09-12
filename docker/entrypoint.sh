#!/bin/sh

set -e

echo "[entrypoint] Starting container with role: ${CONTAINER_ROLE:-web}"

mkdir -p /app/storage/database \
         /app/storage/framework/views \
         /app/storage/framework/cache/data \
         /app/storage/framework/sessions \
         /app/storage/logs \
         /app/storage/app/media \
         /app/bootstrap/cache

if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
    touch /app/storage/database/database.sqlite
fi

# if [ "$(id -u)" = "0" ]; then
    # chown -R www-data:www-data /app/storage /app/bootstrap/cache
# fi

if [ "${RUN_MIGRATIONS:-1}" = "1" ]; then
    echo "[entrypoint] Running database migrations"
    php artisan migrate --force
else
    echo "[entrypoint] Database migrations disabled"
fi

echo "[entrypoint] Starting process"

case "${CONTAINER_ROLE:-web}" in
    queue)
        exec php artisan queue:work --sleep=1 --tries=1 --timeout=120
        ;;
    scheduler)
        exec php artisan schedule:work
        ;;
    web)
        exec "$@"
        ;;
    *)
        echo "Unknown CONTAINER_ROLE: ${CONTAINER_ROLE}" >&2
        exit 1
        ;;
esac
