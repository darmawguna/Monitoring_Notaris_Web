FROM php:8.2-fpm-alpine
WORKDIR /var/www/html
# OS deps
RUN apk add --no-cache supervisor libzip-dev zip unzip \
    libpng-dev libjpeg-turbo-dev freetype-dev icu-dev libxml2-dev bash

# PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install pdo_mysql zip gd intl bcmath opcache

# OPcache (prod)
RUN { \
  echo 'opcache.enable=1'; \
  echo 'opcache.enable_cli=0'; \
  echo 'opcache.validate_timestamps=0'; \
  echo 'opcache.max_accelerated_files=20000'; \
  echo 'opcache.memory_consumption=256'; \
  echo 'opcache.interned_strings_buffer=16'; \
} > /usr/local/etc/php/conf.d/opcache.ini

# Supervisor: jalankan php-fpm (+opsional queue worker)
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

EXPOSE 9000
CMD ["/usr/bin/supervisord","-c","/etc/supervisor/conf.d/supervisord.conf"]
