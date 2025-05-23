FROM php:8.2-fpm

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    unzip zip curl git libzip-dev libldap2-dev libsasl2-dev libkrb5-dev \
    && docker-php-ext-install zip ldap \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Copy Composer from official image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Optional: copy custom php.ini

WORKDIR /var/www/html
