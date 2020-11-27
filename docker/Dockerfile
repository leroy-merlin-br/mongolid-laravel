FROM leroymerlinbr/php:7.3
LABEL maintainer="boitata@leroymerlin.com.br"

USER root:root

RUN docker-php-ext-enable xdebug

ARG UID=1000
ARG GID=1000

RUN groupmod -g ${GID} www-data \
  && usermod -u ${UID} -g www-data www-data \
  && chown -hR www-data:www-data \
    /var/www \
    /usr/local/

COPY custom.ini /usr/local/etc/php/conf.d/custom.ini

USER www-data:www-data
