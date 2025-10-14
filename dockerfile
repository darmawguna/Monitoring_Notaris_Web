# =========================
# 1) Base PHP (Alpine) + extensions for Laravel 12 + Filament v3
# =========================
FROM php:8.2-fpm-alpine AS php_base

# Set environment variables for production
ENV COMPOSER_ALLOW_SUPERUSER=1 \
    APP_ENV=production \
    PHP_OPCACHE_VALIDATE_TIMESTAMPS=0

# Install runtime dependencies
RUN apk add --no-cache \
    git curl zip unzip tzdata bash shadow \
    icu-libs icu-data-full \
    libzip freetype libpng libjpeg-turbo libxml2

# Install build dependencies, will be removed later
RUN apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS icu-dev libzip-dev \
    freetype-dev libpng-dev libjpeg-turbo-dev libxml2-dev

# Configure and install essential PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j"$(nproc)" \
    bcmath exif intl gd pdo_mysql zip opcache xml dom pcntl

# Optimized OPcache settings for production
RUN { \
      echo 'opcache.enable=1'; \
      echo 'opcache.memory_consumption=256'; \
      echo 'opcache.interned_strings_buffer=32'; \
      echo 'opcache.max_accelerated_files=20000'; \
      echo 'opcache.validate_timestamps=${PHP_OPCACHE_VALIDATE_TIMESTAMPS}'; \
      echo 'opcache.jit_buffer_size=100M'; \
      echo 'opcache.jit=1235'; \
    } > /usr/local/etc/php/conf.d/opcache.ini

# Clean up build dependencies
RUN apk del .build-deps

# Set user and group IDs
ARG PUID=1000
ARG PGID=1000
RUN usermod -u "${PUID}" www-data && groupmod -g "${PGID}" www-data

WORKDIR /var/www/html

# Add Composer binary
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer


# =========================
# 2) Builder Stage (Composer + App Code)
# =========================
FROM php_base AS builder
WORKDIR /var/www/html

# Install Composer dependencies
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --optimize-autoloader

# Salin seluruh aplikasi
COPY . .

# --- INI ADALAH PERBAIKAN UTAMA ---
# Jalankan perintah ini untuk mempublikasikan aset dari Filament/Livewire
# Ini menggantikan kebutuhan untuk `npm run build`.
RUN php artisan filament:assets

# Lanjutkan dengan optimasi Laravel lainnya
RUN php artisan optimize
RUN php artisan view:cache
RUN php artisan filament:cache-components


# =========================
# 3) Production image (final)
# =========================
FROM php_base AS production
WORKDIR /var/www/html

# Salin aplikasi yang sudah teroptimasi dari tahap builder
# Ini sekarang sudah termasuk aset frontend yang dipublikasikan
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