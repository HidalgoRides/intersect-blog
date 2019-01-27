# install composer dependencies
FROM composer as composer
COPY ./composer.* /app/
RUN composer install --ignore-platform-reqs --no-scripts

# install PHP / Apache
FROM php:7.1-apache

WORKDIR /var/www

RUN apt-get update && \
    apt-get install -y git zip unzip && \
    docker-php-ext-install mysqli pdo pdo_mysql && \
    apt-get -y autoremove && apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# copy all files to root directory
COPY . .

# copy vendor dependencies to root directory
COPY --from=composer /app/vendor /var/www/vendor