# --- Tahap 1: Build Aset Frontend (Tetap Sama) ---
# Menggunakan image Node.js sebagai 'builder' sementara
FROM node:20-alpine AS frontend_builder
WORKDIR /app
COPY package*.json ./
RUN npm install
COPY . .
RUN npm run build


# --- Tahap 2: Build Image Produksi Final (Gabungan Backend & Final) ---
# Memulai dari image PHP FPM yang bersih dan ringan
FROM php:8.2-fpm-alpine
WORKDIR /var/www/html

# 1. INSTAL SEMUA DEPENDENSI TERLEBIH DAHULU
# Instal ekstensi OS dan PHP yang dibutuhkan
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

# Instal Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer


# 2. INSTAL DEPENDENSI COMPOSER
# Salin hanya file-file ini untuk memanfaatkan caching Docker
COPY composer.json composer.lock ./
# Jalankan composer install di lingkungan yang sudah memiliki semua ekstensi
RUN composer install --no-dev --no-interaction --optimize-autoloader


# 3. SALIN KODE APLIKASI & LAKUKAN OPTIMASI
# Salin sisa kode aplikasi
COPY . .
# Buat cache untuk konfigurasi, rute, event, dan view
RUN php artisan optimize
RUN php artisan view:cache
# Buat cache khusus untuk komponen-komponen Filament
RUN php artisan filament:cache-components


# 4. SALIN ASET FRONTEND & FINALISASI
# Salin hanya aset frontend yang sudah di-build dari frontend_builder
COPY --from=frontend_builder /app/public/build ./public/build

# Salin file konfigurasi Supervisor
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Atur kepemilikan file
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Expose port untuk PHP-FPM
EXPOSE 9000
# Jalankan Supervisor
CMD ["/usr/bin/supervisord","-c","/etc/supervisor/conf.d/supervisord.conf"]