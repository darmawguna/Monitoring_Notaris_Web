FROM php:8.2-fpm-alpine
WORKDIR /var/www/html

# 1. INSTAL SEMUA DEPENDENSI PHP
RUN apk add --no-cache supervisor libzip-dev zip unzip \
    libpng-dev libjpeg-turbo-dev freetype-dev icu-dev libxml2-dev

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install pdo_mysql zip gd intl bcmath opcache

RUN { \
  echo 'opcache.enable=1'; \
  echo 'opcache.enable_cli=0'; \
  echo 'opcache.validate_timestamps=0'; \
  echo 'opcache.max_accelerated_files=20000'; \
  echo 'opcache.memory_consumption=256'; \
  echo 'opcache.interned_strings_buffer=16'; \
} > /usr/local/etc/php/conf.d/opcache.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# 2. INSTAL DEPENDENSI COMPOSER (TANPA MENJALANKAN SKRIP)
COPY composer.json composer.lock ./

# --- PERBAIKAN DI SINI ---
# Tambahkan --no-scripts untuk mencegah error 'artisan not found'
RUN composer install --no-dev --no-interaction --optimize-autoloader --no-scripts

# 3. SALIN KODE APLIKASI & LAKUKAN OPTIMASI
# Kode yang disalin sekarang sudah termasuk /public/build
COPY . .
# Jalankan optimasi sekarang, setelah semua file aplikasi (termasuk 'artisan') sudah ada
RUN php artisan optimize
RUN php artisan view:cache
RUN php artisan filament:cache-components

# 4. FINALISASI
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 9000
CMD ["/usr/bin/supervisord","-c","/etc/supervisor/conf.d/supervisord.conf"]