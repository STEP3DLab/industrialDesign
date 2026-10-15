FROM php:8.3-apache-bookworm
ARG MOODLE_COMMIT=215f44380bdc3ccf852a3a22e34f8423ff64033a
RUN apt-get update && apt-get install -y --no-install-recommends \
    ca-certificates curl unzip libicu-dev libpq-dev libzip-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev libonig-dev libxml2-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" intl pgsql pdo_pgsql zip gd mbstring soap exif opcache \
    && rm -rf /var/lib/apt/lists/*
RUN curl -fsSL --retry 3 "https://codeload.github.com/moodle/moodle/tar.gz/${MOODLE_COMMIT}" \
    | tar -xz --strip-components=1 -C /var/www/html \
    && mkdir -p /var/moodledata && chown www-data:www-data /var/moodledata
COPY docker/php.ini /usr/local/etc/php/conf.d/moodle.ini
COPY docker/apache.conf /etc/apache2/conf-enabled/moodle.conf
COPY docker/config.php /var/www/html/config.php
COPY local/technopark /var/www/html/local/technopark
COPY course /opt/course
COPY branding /opt/branding
COPY scripts /opt/lab/scripts
RUN chmod +x /opt/lab/scripts/*.sh
WORKDIR /var/www/html
