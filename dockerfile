# =========================
# 1) Base PHP (Alpine) + extensions
# =========================
FROM php:8.2-fpm-alpine AS php_base

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    APP_ENV=production \
    PHP_OPCACHE_VALIDATE_TIMESTAMPS=0

RUN apk add --no-cache \
    git curl zip unzip tzdata bash shadow \
    icu-libs icu-data-full \
    libzip freetype libpng libjpeg-turbo libxml2

RUN apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS icu-dev libzip-dev \
    freetype-dev libpng-dev libjpeg-turbo-dev libxml2-dev

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j"$(nproc)" \
    bcmath exif intl gd pdo_mysql zip opcache xml dom pcntl

RUN { \
      echo 'opcache.enable=1'; \
      echo 'opcache.memory_consumption=256'; \
      echo 'opcache.interned_strings_buffer=32'; \
      echo 'opcache.max_accelerated_files=20000'; \
      echo 'opcache.validate_timestamps=${PHP_OPCACHE_VALIDATE_TIMESTAMPS}'; \
      echo 'opcache.jit_buffer_size=100M'; \
      echo 'opcache.jit=1235'; \
    } > /usr/local/etc/php/conf.d/opcache.ini
RUN apk del .build-deps

ARG PUID=1000
ARG PGID=1000
RUN usermod -u "${PUID}" www-data && groupmod -g "${PGID}" www-data

WORKDIR /var/www/html
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# =========================
# 2) Composer dependencies
# =========================
FROM php_base AS deps
WORKDIR /app

COPY composer.json composer.lock ./
# --- PERBAIKAN DI SINI ---
# Jalankan install dengan --no-scripts untuk mencegah error 'artisan not found'
RUN composer install --no-dev --no-interaction --no-autoloader --no-scripts

# =========================
# 3) Frontend assets build
# =========================
FROM node:20-bookworm AS assets
WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --no-audit

COPY . .
RUN npm run build

# =========================
# 4) Production image (final)
# =========================
FROM php_base AS production
WORKDIR /var/www/html

# Salin semua artefak yang sudah dibangun
COPY --chown=www-data:www-data . .
COPY --chown=www-data:www-data --from=deps /app/vendor ./vendor
COPY --chown=www-data:www-data --from=assets /app/public/build ./public/build
COPY --chown=www-data:www-data docker/template/ /var/www/html/storage/app/template/

# --- PERBAIKAN DI SINI ---
# Jalankan semua optimasi SEKARANG, setelah semua file ada di tempatnya
RUN composer dump-autoload --optimize --classmap-authoritative \
 && php artisan storage:link \
 && php artisan optimize \
 && php artisan filament:cache-components

# Atur permissions
RUN chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000
USER www-data
CMD ["php-fpm"]