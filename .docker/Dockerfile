FROM php:8.3-apache

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    git zip unzip curl libpng-dev libonig-dev libxml2-dev libzip-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd xml zip

# Enable Apache rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php

RUN mv composer.phar /usr/local/bin/composer
RUN chmod +x /usr/local/bin/composer
# COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy Apache vhost
COPY .docker/vhost.conf /etc/apache2/sites-available/000-default.conf