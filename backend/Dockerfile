FROM brettt89/silverstripe-web:7.4-apache

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/bin/install-php-extensions
RUN install-php-extensions xdebug

RUN echo "date.timezone = Europe/Amsterdam" >> /usr/local/etc/php/conf.d/timezone.ini && \
    echo "xdebug.mode=debug" >> /usr/local/etc/php/conf.d/xdebug.ini && \
    echo "xdebug.client_host = host.docker.internal" >> /usr/local/etc/php/conf.d/xdebug.ini