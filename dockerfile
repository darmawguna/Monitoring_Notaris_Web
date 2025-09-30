# ============================================
# STAGE 1: Build Frontend Assets
# ============================================
FROM node:18-alpine AS assets

WORKDIR /app

# Install dependencies
COPY package*.json ./
RUN npm ci

# Copy all needed source files & configs
COPY vite.config.js ./
COPY tailwind.config.js ./   
COPY postcss.config.js ./    
COPY resources ./resources
# Tidak perlu menyalin 'public' karena build akan men-generate isinya

# Build assets
RUN npm run build
# ============================================
# STAGE 2: PHP Dependencies & Optimization
# ============================================
FROM php:8.2-fpm-alpine AS builder

WORKDIR /var/www/html

# Install PHP extensions dan dependencies
RUN apk add --no-cache \
    libzip-dev zip unzip \
    libpng-dev libjpeg-turbo-dev freetype-dev \
    icu-dev libxml2-dev oniguruma-dev \
    git

# Configure & install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    zip \
    gd \
    intl \
    bcmath \
    opcache \
    mbstring

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy dependency files
COPY composer.json composer.lock ./

# Install dependencies (no dev, no scripts yet)
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --optimize-autoloader \
    --no-scripts \
    --prefer-dist

# Copy application code
COPY . .

# Copy built assets from Stage 1
COPY --from=assets /app/public/build ./public/build

# Generate optimized autoloader (now artisan exists)
RUN composer dump-autoload --optimize

# ============================================
# STAGE 3: Production Runtime
# ============================================
FROM php:8.2-fpm-alpine

WORKDIR /var/www/html

# Install runtime dependencies only
RUN apk add --no-cache \
    supervisor \
    libzip libpng libjpeg-turbo freetype \
    icu-libs libxml2 oniguruma \
    mysql-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql zip gd intl bcmath opcache mbstring

# OPcache configuration for production
RUN { \
    echo 'opcache.enable=1'; \
    echo 'opcache.enable_cli=0'; \
    echo 'opcache.validate_timestamps=0'; \
    echo 'opcache.revalidate_freq=0'; \
    echo 'opcache.max_accelerated_files=20000'; \
    echo 'opcache.memory_consumption=256'; \
    echo 'opcache.interned_strings_buffer=16'; \
    echo 'opcache.fast_shutdown=1'; \
} > /usr/local/etc/php/conf.d/opcache.ini

# PHP-FPM pool configuration
RUN { \
    echo '[www]'; \
    echo 'pm = dynamic'; \
    echo 'pm.max_children = 50'; \
    echo 'pm.start_servers = 5'; \
    echo 'pm.min_spare_servers = 5'; \
    echo 'pm.max_spare_servers = 35'; \
    echo 'pm.max_requests = 500'; \
} > /usr/local/etc/php-fpm.d/zz-docker.conf

# Copy optimized application from builder
COPY --from=builder --chown=www-data:www-data /var/www/html /var/www/html

# Copy supervisor config
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Create storage directories with correct permissions
RUN mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache \
 && chmod -R 775 storage bootstrap/cache

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=40s \
  CMD php-fpm-healthcheck || exit 1

EXPOSE 9000

USER www-data

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]