FROM composer:latest as composer_stage

WORKDIR /app

COPY composer.lock /app
COPY composer.json /app

RUN composer install --ignore-platform-reqs --prefer-dist --no-scripts --no-progress --no-interaction --no-dev

FROM php:8.2-fpm-buster

WORKDIR /app

COPY --from=composer_stage /app /app

# libpq-dev - Required for pdo_pgsql extenstion
# imagemagick - Required for generated files
# libmagickwand-dev - ImageMagick for generated files
RUN apt-get update
RUN apt-get install -y -q --no-install-recommends \
    libpq-dev \
    libmagickwand-dev

# https://stackoverflow.com/questions/70695447/docker-php8-1-1-fpm-how-to-install-imagick-php-extension
RUN pecl install imagick

# pdo pdo_pgsql - PostgreSQL driver
# bcmath - Required for php-amqplib/php-amqplib library
# sockets - Required for php-amqplib/php-amqplib library
RUN docker-php-ext-install pdo pdo_pgsql bcmath sockets

# imagick - ImageMagick extension for generated files
RUN docker-php-ext-enable imagick

# Copy php config
COPY _docker/php/prod/base.ini /usr/local/etc/php/conf.d/base.ini

# Copy src folder
COPY src /app/src

# Copy entrypoints
COPY amqp_consumer.php /app
COPY cli.php /app
COPY index.php /app

RUN mkdir -p /app/uploads
RUN mkdir -p /app/generated

VOLUME /app/uploads
VOLUME /app/generated

ARG wwwdatauid=1000
RUN usermod -u $wwwdatauid www-data
RUN chown -R www-data:www-data /app