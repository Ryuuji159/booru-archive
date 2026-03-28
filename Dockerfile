FROM dunglas/frankenphp AS base

WORKDIR /app

ENV SERVER_NAME=:80

RUN install-php-extensions intl pcntl pdo_sqlite opcache zip

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

FROM base AS vendor

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts

FROM node AS assets

WORKDIR /app

COPY package.json package-lock.json vite.config.js ./
COPY resources ./resources
COPY public ./public
COPY --from=vendor /app/vendor ./vendor

RUN npm ci
RUN npm run build

FROM base AS runtime

WORKDIR /app

COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build

RUN mkdir -p storage/framework/views \
            storage/framework/cache/data \
            storage/framework/sessions \
            storage/database \
            storage/app/media \
            bootstrap/cache \
            database \
    && chown -R www-data:www-data storage bootstrap/cache database \
    && chmod -R ug+rwX storage bootstrap/cache database

RUN rm -f bootstrap/cache/*.php \
    && php artisan package:discover --ansi --no-interaction

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

USER root

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php", "artisan", "octane:frankenphp", "--host=0.0.0.0", "--port=80"]

EXPOSE 80
