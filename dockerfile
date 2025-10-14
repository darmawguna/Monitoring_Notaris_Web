# Menggunakan base image PHP FPM yang ringan
FROM php:8.2-fpm-alpine

# Instal semua dependensi yang dibutuhkan dalam satu layer
RUN apk add --no-cache \
    nginx supervisor git curl zip unzip bash shadow \
    icu-dev libzip-dev freetype-dev libpng-dev libjpeg-turbo-dev libxml2-dev

# Instal ekstensi PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j"$(nproc)" pdo_mysql zip gd intl bcmath opcache

# Instal Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Instal Node.js dan npm
RUN apk add --no-cache nodejs npm

WORKDIR /var/www/html

# Salin semua file dari konteks build (dari direktori proyek di VPS)
COPY . .

# Jalankan instalasi dependensi backend dan frontend
RUN composer install --no-dev --no-interaction --optimize-autoloader
RUN npm ci
RUN npm run build

# Jalankan optimasi Laravel
RUN php artisan optimize:clear
RUN php artisan optimize
RUN php artisan view:cache
RUN php artisan filament:assets
RUN php artisan filament:cache-components

# Salin konfigurasi Supervisor
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Atur kepemilikan file
RUN chown -R www-data:www-data storage bootstrap/cache public/build

EXPOSE 9000
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]