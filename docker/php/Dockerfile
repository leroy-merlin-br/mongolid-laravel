FROM leroymerlinbr/php:7.2

USER root

RUN pecl install xdebug \
  && docker-php-ext-enable xdebug

COPY custom.ini /usr/local/etc/php/conf.d/custom.ini

USER www-data:www-data
