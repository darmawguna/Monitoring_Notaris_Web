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
    freetype-dev libpng-dev libjpeg-turbo-dev libxml2-dev \
    libxml2-utils

# Configure and install essential PHP extensions for Laravel 12 & Filament
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j"$(nproc)" \
    bcmath exif intl gd pdo_mysql zip opcache xml dom pcntl

# Optimized OPcache settings for production with JIT enabled
RUN { \
      echo 'opcache.enable=1'; \
      echo 'opcache.enable_cli=1'; \
      echo 'opcache.memory_consumption=256'; \
      echo 'opcache.interned_strings_buffer=32'; \
      echo 'opcache.max_accelerated_files=20000'; \
      echo 'opcache.validate_timestamps=${PHP_OPCACHE_VALIDATE_TIMESTAMPS}'; \
      echo 'opcache.jit_buffer_size=100M'; \
      echo 'opcache.jit=1235'; \
   } > /usr/local/etc/php/conf.d/opcache.ini

# Clean up build dependencies to keep the image slim
RUN apk del .build-deps

# Set user and group IDs
ARG PUID=1000
ARG PGID=1000
RUN usermod -u "${PUID}" www-data && groupmod -g "${PGID}" www-data

WORKDIR /var/www/html

# Add Composer binary from its official image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# =========================
# 2) Composer dependencies
# =========================
FROM php_base AS deps
WORKDIR /app

# Copy only necessary files and install Composer dependencies
COPY composer.json composer.lock* ./
# RUN composer install --no-dev --prefer-dist --no-progress --no-interaction --no-scripts \
#  && composer dump-autoload --classmap-authoritative --no-interaction
RUN composer install --no-dev --prefer-dist --no-scripts -vvv

# =========================
# 3) Frontend assets build
# =========================
FROM node:20-bookworm AS assets
WORKDIR /app

RUN npm config set fund false && npm config set audit false

COPY package.json package-lock.json* pnpm-lock.yaml* yarn.lock* ./
RUN if [ -f pnpm-lock.yaml ]; then corepack enable && corepack prepare pnpm@latest --activate && pnpm i --frozen-lockfile; \
    elif [ -f yarn.lock ]; then corepack enable && yarn install --frozen-lockfile; \
    else npm ci --no-audit; fi

COPY . .
RUN npm run build

# =========================
# 4) Production image (final)
# =========================
FROM php_base AS production
WORKDIR /var/www/html

# Copy application code, vendor, and built assets with correct ownership
COPY --chown=www-data:www-data . .
COPY --chown=www-data:www-data --from=deps /app/vendor ./vendor
COPY --chown=www-data:www-data --from=assets /app/public/build ./public/build

# Set correct permissions and run optimizations
RUN mkdir -p storage/framework/{cache,sessions,views} bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap \
 && chmod -R 775 storage bootstrap/cache \
 && php artisan storage:link

# Expose PHP-FPM port
EXPOSE 9000

# Set user to non-root
USER www-data

# Start PHP-FPM
CMD ["php-fpm"]

#  && php artisan config:cache \
#  && php artisan route:cache \
#  && php artisan view:cache