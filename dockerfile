# =========================
# 1) Base PHP (Alpine) + extensions for Laravel 12 + Filament v3
# =========================
FROM php:8.2-fpm-alpine AS php_base

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    APP_ENV=production \
    PHP_OPCACHE_VALIDATE_TIMESTAMPS=0

# Runtime deps
RUN apk add --no-cache \
    git curl zip unzip tzdata bash shadow \
    icu-libs icu-data-full \
    libzip freetype libpng libjpeg-turbo

# Build deps (hapus setelah compile extension)
RUN apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS icu-dev libzip-dev \
    freetype-dev libpng-dev libjpeg-turbo-dev

# PHP extensions (Filament/PhpWord/Laravel)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j"$(nproc)" \
    bcmath exif intl gd pdo_mysql zip opcache

# Opcache tuning (production)
RUN { \
      echo 'opcache.enable=1'; \
      echo 'opcache.enable_cli=0'; \
      echo 'opcache.jit_buffer_size=0'; \
      echo 'opcache.memory_consumption=256'; \
      echo 'opcache.interned_strings_buffer=32'; \
      echo 'opcache.max_accelerated_files=20000'; \
      echo 'opcache.validate_timestamps=${PHP_OPCACHE_VALIDATE_TIMESTAMPS}'; \
   } > /usr/local/etc/php/conf.d/opcache.ini

# Bersihkan build deps agar base lebih ramping
RUN apk del .build-deps

# Samakan UID/GID (opsional)
ARG PUID=1000
ARG PGID=1000
RUN usermod -u "${PUID}" www-data && groupmod -g "${PGID}" www-data

WORKDIR /var/www/html

# Tambahkan composer binary ke base image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer


# =========================
# 2) Node/Vite build (DEBIAN, stabil)
# =========================
FROM node:20-bookworm AS assets
WORKDIR /app

# Matikan audit/fund
RUN npm config set fund false && npm config set audit false

# Install deps JS sesuai lock yang tersedia
COPY package.json package-lock.json* pnpm-lock.yaml* yarn.lock* ./
RUN if [ -f package-lock.json ]; then npm ci --no-audit --no-fund; \
    elif [ -f yarn.lock ]; then corepack enable && yarn install --frozen-lockfile; \
    elif [ -f pnpm-lock.yaml ]; then corepack enable && corepack prepare pnpm@9 --activate && pnpm i --frozen-lockfile; \
    else npm i --no-audit --no-fund; fi

# Sumber untuk build
COPY resources ./resources
COPY public ./public
COPY vite.config.* ./
COPY postcss.config.* ./
COPY tailwind.config.* ./

# Build produksi (Tailwind v3 tidak butuh binding native)
RUN npm run build


# =========================
# 3) Composer deps (jalan di php_base yg sudah punya ext-intl)
# =========================
FROM php_base AS deps
WORKDIR /app

COPY composer.json composer.lock* ./
RUN composer install --no-dev --prefer-dist --no-progress --no-interaction --no-scripts \
 && composer dump-autoload --classmap-authoritative --no-interaction --no-scripts


# =========================
# 4) Production image (final)
# =========================
FROM php_base AS production
WORKDIR /var/www/html

# Source code
COPY . .

# Vendor dari stage deps
COPY --from=deps /app/vendor ./vendor

# Asset build dari stage assets
COPY --from=assets /app/public/build ./public/build

# Permission & cache
RUN mkdir -p storage/framework/{cache,sessions,views} \
 && mkdir -p bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache \
 && find storage -type d -exec chmod 775 {} \; \
 && find storage -type f -exec chmod 664 {} \; \
 && chmod -R 775 bootstrap/cache \
 && php artisan config:clear || true \
 && php artisan route:clear || true \
 && php artisan view:clear || true \
 && php artisan config:cache || true \
 && php artisan route:cache || true \
 && php artisan view:cache || true

# FPM status/ping (opsional; lindungi di reverse proxy)
RUN { \
    echo "[www]"; \
    echo "pm.status_path = /status"; \
    echo "ping.path = /ping"; \
} > /usr/local/etc/php-fpm.d/zz-status.conf

EXPOSE 9000
HEALTHCHECK --interval=30s --timeout=5s --retries=5 CMD pgrep php-fpm || exit 1

USER www-data
CMD ["php-fpm", "-F"]
