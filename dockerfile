# =========================
# 1) Builder Stage (PHP + App Code)
# =========================
FROM php:8.2-fpm-alpine AS builder

# Instal dependensi OS, PHP, dan Composer
RUN apk add --no-cache libzip-dev zip unzip git curl icu-dev libpng-dev libjpeg-turbo-dev freetype-dev  mysql-client
RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install -j"$(nproc)" pdo_mysql zip gd intl bcmath opcache
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Instal dependensi Composer
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --optimize-autoloader --no-scripts

# Salin seluruh aplikasi (yang sekarang sudah termasuk /public/build)
COPY . .



# =========================
# 2) Production Image (Final)
# =========================
FROM builder AS production

# Instal hanya dependensi runtime
RUN apk add --no-cache supervisor libzip libpng libjpeg-turbo freetype icu-libs

WORKDIR /var/www/html

# Salin aplikasi yang sudah teroptimasi dari tahap builder
COPY --from=builder /var/www/html .

# Salin konfigurasi Supervisor
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Atur kepemilikan file
RUN chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000

USER www-data

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

