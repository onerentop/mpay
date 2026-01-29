# MPay Docker Image
# PHP 8.2 + Nginx + ThinkPHP 8
FROM php:8.2-fpm-alpine

LABEL maintainer="MPay Team"
LABEL description="MPay Payment System - ThinkPHP 8"

# Install system dependencies and Composer
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    git \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    oniguruma-dev \
    icu-dev \
    && rm -rf /var/cache/apk/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    mysqli \
    gd \
    zip \
    mbstring \
    intl \
    opcache \
    fileinfo \
    bcmath

# Configure PHP
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php.ini /usr/local/etc/php/conf.d/custom.ini

# Configure Nginx
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/default.conf /etc/nginx/http.d/default.conf
COPY docker/ssl /etc/nginx/ssl

# Configure Supervisor
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copy entrypoint script
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY --chown=www-data:www-data . /var/www/html

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Create required directories and set permissions
RUN chmod -R 755 /var/www/html \
    && mkdir -p /var/www/html/runtime/session \
    && mkdir -p /var/www/html/runtime/cache \
    && mkdir -p /var/www/html/runtime/log \
    && mkdir -p /var/www/html/runtime/auth \
    && mkdir -p /var/www/html/public/files \
    && mkdir -p /var/www/html/public/config \
    && mkdir -p /var/www/html/extend/payclient \
    && mkdir -p /var/www/html/config/extend \
    && mkdir -p /var/log/php \
    && mkdir -p /var/log/supervisor \
    && chmod -R 777 /var/www/html/runtime \
    && chmod -R 777 /var/www/html/public/config \
    && chmod -R 777 /var/www/html/public/files \
    && chmod -R 777 /var/www/html/extend \
    && chmod -R 777 /var/www/html/config/extend \
    && chmod 777 /var/log/php \
    && chmod 777 /var/log/supervisor

# Expose ports
EXPOSE 80 443

# Use entrypoint script
ENTRYPOINT ["/entrypoint.sh"]
