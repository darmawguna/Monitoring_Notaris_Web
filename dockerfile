# =========================
# 1) Base PHP (Alpine) + extensions
# =========================
FROM php:8.2-fpm-alpine AS php_base

ENV COMPOSER_ALLOW_SUPERUSER=1 APP_ENV=production PHP_OPCACHE_VALIDATE_TIMESTAMPS=0

RUN apk add --no-cache git curl zip unzip tzdata bash shadow icu-libs icu-data-full libzip freetype libpng libjpeg-turbo libxml2
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS icu-dev libzip-dev freetype-dev libpng-dev libjpeg-turbo-dev libxml2-dev
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j"$(nproc)" bcmath exif intl gd pdo_mysql zip opcache xml dom pcntl
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
# 2) Builder Stage (Composer + App Code)
# =========================
FROM php_base AS builder
WORKDIR /var/www/html

# Instal dependensi Composer (tanpa menjalankan skrip)
COPY composer.json composer.lock ./

# --- PERBAIKAN DI SINI ---
# Tambahkan --no-scripts untuk mencegah error 'artisan not found'
RUN composer install --no-dev --no-interaction --optimize-autoloader --no-scripts

# Salin seluruh aplikasi
COPY . .

# --- PINDAHKAN OPTIMASI KE SINI ---
# Jalankan optimasi Laravel SETELAH semua file sudah ada
RUN php artisan optimize
RUN php artisan view:cache
RUN php artisan filament:cache-components
RUN php artisan filament:assets

# =========================
# 3) Production image (final)
# =========================
FROM php_base AS production
WORKDIR /var/www/html

# Salin aplikasi yang sudah teroptimasi dari tahap builder
COPY --chown=www-data:www-data --from=builder /var/www/html .
COPY --chown=www-data:www-data docker/template/ /var/www/html/storage/app/template/

# Set correct permissions and run final commands
RUN php artisan storage:link
RUN chown -R www-data:www-data storage bootstrap/cache && chmod -R 775 storage bootstrap/cache

# Salin konfigurasi Supervisor
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

EXPOSE 9000
USER www-data
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]