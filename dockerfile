# --- Tahap 1: Build Aset Frontend ---
# Menggunakan image Node.js sebagai 'builder' sementara
FROM node:20-alpine AS frontend_builder
WORKDIR /app
COPY package*.json ./
RUN npm install
COPY . .
# Perintah ini akan meminifikasi CSS dan JS Anda
RUN npm run build


# --- Tahap 2: Build Dependensi & Cache Backend ---
# Menggunakan image Composer sebagai 'builder' sementara
FROM composer:2 AS backend_builder
WORKDIR /app
# Salin semua file aplikasi terlebih dahulu
COPY . .
# Instal hanya dependensi produksi dan optimalkan autoloader untuk kecepatan
RUN composer install --no-dev --no-interaction --optimize-autoloader
# --- INI LANGKAH OPTIMASI BARU ---
# Buat cache untuk konfigurasi, rute, event, dan view
RUN php artisan optimize
RUN php artisan view:cache
# Buat cache khusus untuk komponen-komponen Filament
RUN php artisan filament:cache-components


# --- Tahap 3: Image Final Produksi ---
# Memulai dari image PHP FPM yang bersih dan ringan
FROM php:8.2-fpm-alpine
WORKDIR /var/www/html

# Instal ekstensi OS dan PHP yang dibutuhkan (sama seperti sebelumnya)
RUN apk add --no-cache supervisor libzip-dev zip unzip \
    libpng-dev libjpeg-turbo-dev freetype-dev icu-dev libxml2-dev

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install pdo_mysql zip gd intl bcmath opcache

# Salin konfigurasi OPcache untuk performa produksi
RUN { \
  echo 'opcache.enable=1'; \
  echo 'opcache.enable_cli=0'; \
  echo 'opcache.validate_timestamps=0'; \
  echo 'opcache.max_accelerated_files=20000'; \
  echo 'opcache.memory_consumption=256'; \
  echo 'opcache.interned_strings_buffer=16'; \
} > /usr/local/etc/php/conf.d/opcache.ini

# Salin file konfigurasi Supervisor
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# --- Menyalin Artefak yang Sudah Dibangun dan Dioptimasi ---
# Salin seluruh aplikasi (termasuk folder vendor dan cache) dari backend_builder
COPY --from=backend_builder /app .
# Salin hanya aset frontend yang sudah di-build dari frontend_builder
COPY --from=frontend_builder /app/public/build ./public/build

# Atur kepemilikan file agar server web bisa menulis log dan cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Expose port untuk PHP-FPM
EXPOSE 9000
# Jalankan Supervisor
CMD ["/usr/bin/supervisord","-c","/etc/supervisor/conf.d/supervisord.conf"]