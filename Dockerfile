# Roni5 production PHP-FPM runtime image.
# Code is NOT copied in — it is bind-mounted from /srv/roni5 on the host so the
# host Caddy and this container share identical paths for php_fastcgi. vendor/
# and public/build are produced in CI and rsynced to the host.
FROM php:8.4-fpm-bookworm

# Match the host deploy user (gothem = 1000:1000) so files written by the
# container (storage, bootstrap/cache) stay owned by the host user.
ARG UID=1000
ARG GID=1000

RUN apt-get update && apt-get install -y --no-install-recommends \
        libpng-dev libjpeg62-turbo-dev libfreetype6-dev libwebp-dev \
        libzip-dev libicu-dev libonig-dev unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql mbstring bcmath gd intl zip exif opcache \
    && rm -rf /var/lib/apt/lists/*

# Re-point www-data to the host UID/GID so bind-mounted files are read/writable.
RUN groupmod -o -g "${GID}" www-data \
    && usermod  -o -u "${UID}" -g "${GID}" www-data

COPY deploy/php/opcache.ini /usr/local/etc/php/conf.d/zz-opcache.ini
COPY deploy/php/app.ini     /usr/local/etc/php/conf.d/zz-app.ini

WORKDIR /srv/roni5

# php-fpm listens on 0.0.0.0:9000 (default); host Caddy connects via 127.0.0.1:9000.
EXPOSE 9000
CMD ["php-fpm"]
