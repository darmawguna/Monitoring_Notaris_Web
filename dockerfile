# ============================================
# Stage 1: Build (Composer + Node dependencies)
# ============================================
FROM php:8.3-fpm-alpine AS build

RUN apk add --no-cache \
    git \
    curl \
    zip \
    unzip \
    libpng-dev \
    libxml2-dev \
    icu-dev \
    libzip-dev \
    oniguruma-dev \
    nodejs \
    npm

# Install PHP extensions needed for build
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd intl zip

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy composer files first (layer caching)
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

# Copy package.json and build frontend
COPY package.json package-lock.json ./
RUN npm ci

# Copy all source code
COPY . .

# Run composer scripts (post-autoload-dump)
RUN composer run-script post-autoload-dump

# Build Vite assets
RUN npm run build

# Remove node_modules after build (not needed at runtime)
RUN rm -rf node_modules

# ============================================
# Stage 2: Production runtime
# ============================================
FROM php:8.3-fpm-alpine AS production

LABEL maintainer="NotarisKu Team"
LABEL description="NotarisKu production image"

# Install runtime dependencies only
RUN apk add --no-cache \
    libpng \
    libxml2 \
    icu-libs \
    libzip \
    oniguruma \
    fcgi \
    curl

# Install php-fpm-healthcheck script
RUN wget -O /usr/local/bin/php-fpm-healthcheck \
    https://raw.githubusercontent.com/renatomefi/php-fpm-healthcheck/master/php-fpm-healthcheck \
    && chmod +x /usr/local/bin/php-fpm-healthcheck

# Install PHP extensions
RUN apk add --no-cache --virtual .build-deps \
    libpng-dev \
    libxml2-dev \
    icu-dev \
    libzip-dev \
    oniguruma-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd intl zip opcache \
    && apk del .build-deps

# Configure OPcache for production
RUN { \
    echo 'opcache.enable=1'; \
    echo 'opcache.memory_consumption=128'; \
    echo 'opcache.interned_strings_buffer=16'; \
    echo 'opcache.max_accelerated_files=10000'; \
    echo 'opcache.validate_timestamps=0'; \
    echo 'opcache.save_comments=1'; \
    echo 'opcache.fast_shutdown=1'; \
    } > /usr/local/etc/php/conf.d/opcache.ini

# Configure PHP for production
RUN { \
    echo 'upload_max_filesize=100M'; \
    echo 'post_max_size=108M'; \
    echo 'memory_limit=256M'; \
    echo 'max_execution_time=300'; \
    echo 'expose_php=Off'; \
    echo 'display_errors=Off'; \
    echo 'log_errors=On'; \
    echo 'error_log=/dev/stderr'; \
    } > /usr/local/etc/php/conf.d/production.ini

# Configure PHP-FPM
RUN { \
    echo '[global]'; \
    echo 'error_log = /proc/self/fd/2'; \
    echo 'daemonize = no'; \
    echo ''; \
    echo '[www]'; \
    echo 'user = www-data'; \
    echo 'group = www-data'; \
    echo 'listen = 9000'; \
    echo 'listen.mode = 0660'; \
    echo 'listen.owner = www-data'; \
    echo 'listen.group = www-data'; \
    echo 'pm = dynamic'; \
    echo 'pm.max_children = 20'; \
    echo 'pm.start_servers = 4'; \
    echo 'pm.min_spare_servers = 2'; \
    echo 'pm.max_spare_servers = 6'; \
    echo 'pm.max_requests = 500'; \
    echo 'clear_env = no'; \
    echo 'catch_workers_output = yes'; \
    echo 'decorate_workers_output = no'; \
    echo 'access.log = /proc/self/fd/2'; \
    echo 'ping.path = /ping'; \
    echo 'pm.status_path = /status'; \
    } > /usr/local/etc/php-fpm.d/zz-production.conf

WORKDIR /var/www/html

# Copy built application from build stage
COPY --from=build --chown=www-data:www-data /var/www/html /var/www/html

# Copy templates to ensure they're always available
COPY --chown=www-data:www-data docker/template/ /var/www/html/storage/app/template/

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Healthcheck: verify PHP-FPM is responding
HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD SCRIPT_NAME=/ping SCRIPT_FILENAME=/ping REQUEST_METHOD=GET cgi-fcgi -bind -connect 127.0.0.1:9000 || exit 1

USER www-data

EXPOSE 9000

CMD ["php-fpm"]
