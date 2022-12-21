FROM php:7.4-fpm
LABEL maintainer="boitata@leroymerlin.com.br"

USER root:root

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

RUN apt-get update -qq \
  && apt-get install -qq --no-install-recommends \
    git zip unzip \
    libzip-dev libssl-dev \
    zlib1g-dev libicu-dev \
    && apt-get clean

RUN pecl install xdebug-3.1.6 mongodb \
  && docker-php-ext-enable \
    mongodb xdebug \
  && docker-php-ext-configure  \
    intl \
  && docker-php-ext-install  \
    intl pcntl zip \
  && rm -rf /tmp/*

ARG UID=1000
ARG GID=1000

RUN groupmod -g ${GID} www-data \
  && usermod -u ${UID} -g www-data www-data \
  && mkdir -p /var/www/html \
  && chown -hR www-data:www-data \
    /var/www \
    /usr/local/

COPY custom.ini /usr/local/etc/php/conf.d/custom.ini

USER www-data:www-data

WORKDIR /var/www/html
ENV PATH=$PATH:/var/www/.composer/vendor/bin
