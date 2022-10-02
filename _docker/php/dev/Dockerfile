FROM php:8.1-fpm-buster

# Install composer in image
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

#  libpq-dev - Required for pdo_pgsql extenstion
#  git - Required for composer
RUN apt-get update
RUN apt-get install -y -q --no-install-recommends libpq-dev git

# pdo pdo_pgsql - PostgreSQL driver
# bcmath - Required for php-amqplib/php-amqplib library
# sockets - Required for php-amqplib/php-amqplib library
RUN docker-php-ext-install pdo pdo_pgsql bcmath sockets