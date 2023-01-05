FROM php:8.2-fpm-bullseye

# Install composer in image
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# libpq-dev - Required for pdo_pgsql extenstion
# git - Required for composer
# libmagickwand-dev - ImageMagick for generated files
RUN apt-get update
RUN apt-get install -y -q --no-install-recommends \
    libpq-dev \
    git \
    libmagickwand-dev

# https://stackoverflow.com/questions/70695447/docker-php8-1-1-fpm-how-to-install-imagick-php-extension
RUN pecl install imagick

# pdo pdo_pgsql - PostgreSQL driver
# bcmath - Required for php-amqplib/php-amqplib library
# sockets - Required for php-amqplib/php-amqplib library
# opcache - Opcache and JIT for performance incrase
RUN docker-php-ext-install  \
    pdo \
    pdo_pgsql \
    bcmath \
    sockets \
    opcache

# imagick - ImageMagick extension for generated files
RUN docker-php-ext-enable imagick