FROM composer:2 AS vendor

WORKDIR /app
COPY composer.json composer.lock* symfony.lock* ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts \
    --ignore-platform-req=ext-pdo_pgsql

FROM php:8.4-cli-alpine

RUN apk add --no-cache postgresql-dev icu-dev libzip-dev \
    && docker-php-ext-install pdo_pgsql intl opcache

WORKDIR /app

COPY --from=vendor /app/vendor ./vendor
COPY . .

RUN mkdir -p var/cache var/log && chmod -R 775 var \
    && printf 'APP_ENV=prod\n' > .env \
    && chmod +x docker/railway-start.sh

ENV APP_ENV=prod
ENV APP_DEBUG=0

EXPOSE 8000

COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]
