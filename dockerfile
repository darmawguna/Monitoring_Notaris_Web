# --- Tahap 1: Persiapan Dasar ---
# Gunakan image resmi PHP 8.2 FPM versi Alpine (ringan) sebagai dasar
FROM php:8.2-fpm-alpine

# Tetapkan direktori kerja di dalam kontainer
WORKDIR /var/www/html

# Instal dependensi sistem yang dibutuhkan oleh Laravel & Filament
RUN apk add --no-cache \
    supervisor \
    libzip-dev \
    zip \
    unzip \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    nodejs \
    npm

# Instal ekstensi PHP yang umum digunakan untuk Laravel
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
    pdo_mysql \
    zip \
    gd

# Instal Composer (manajer dependensi PHP) secara global
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# --- Tahap 2: Instalasi Dependensi & Build Aset ---
# Salin file konfigurasi Supervisor
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Salin file dependensi & instal vendor
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-plugins --prefer-dist

# Salin seluruh kode aplikasi
COPY . .

# Bangun aset frontend untuk produksi
RUN npm install && npm run build

# --- Tahap 3: Finalisasi ---
# Atur kepemilikan file
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Expose port 9000 untuk PHP-FPM agar bisa diakses oleh Caddy di host
EXPOSE 9000

# Jalankan Supervisor sebagai perintah utama
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]