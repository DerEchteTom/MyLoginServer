FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    apt-utils \
    libssl-dev \
    libzip-dev \
    libldap2-dev \
    libsasl2-dev \
    unzip \
    git \
    autoconf \
    pkg-config \
    libtool \
    && docker-php-source extract \
    && cd /usr/src/php/ext/ldap \
    && phpize \
    && ./configure --with-ldap --with-ldap-sasl \
    && make && make install \
    && echo "extension=ldap.so" > /usr/local/etc/php/conf.d/docker-ldap.ini \
    && docker-php-source delete

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
