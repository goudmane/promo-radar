FROM composer:2 AS deps
WORKDIR /build
RUN composer create-project laravel/laravel:^13.0 . --no-dev --no-interaction
COPY . /overlay
RUN cp -a /overlay/. /build/ && composer require laravel/sanctum:^4.0 --no-dev --no-interaction --no-progress

FROM php:8.3-cli
RUN apt-get update && apt-get install -y --no-install-recommends libpq-dev libzip-dev libonig-dev unzip && docker-php-ext-install pdo_pgsql zip mbstring pcntl && pecl install redis && docker-php-ext-enable redis && rm -rf /var/lib/apt/lists/*
WORKDIR /app
COPY --from=deps /build /app
RUN mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache && chown -R www-data:www-data storage bootstrap/cache
EXPOSE 8000
