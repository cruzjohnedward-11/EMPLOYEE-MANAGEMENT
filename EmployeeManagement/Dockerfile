FROM php:8.4-fpm-bookworm

RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends ca-certificates libcurl4 libcurl4-openssl-dev; \
    docker-php-ext-install -j"$(nproc)" curl pdo_mysql; \
    sed -i 's/^;clear_env = no$/clear_env = no/' /usr/local/etc/php-fpm.d/www.conf; \
    apt-get purge -y --auto-remove libcurl4-openssl-dev; \
    rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

EXPOSE 9000
