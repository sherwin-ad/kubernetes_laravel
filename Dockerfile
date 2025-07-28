# Use a base image with Apache and PHP-FPM
FROM php:8.3-apache

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    git zip unzip curl libpng-dev libonig-dev libxml2-dev libzip-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd xml zip

# Enable Apache rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy Laravel application files
COPY ./agg .

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php

RUN mv composer.phar /usr/local/bin/composer
RUN chmod +x /usr/local/bin/composer
# COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN composer install

# Run Laravel artisan commands (for initial setup)
RUN cp .env.example .env
RUN php artisan key:generate
RUN php artisan config:clear
RUN php artisan cache:clear
RUN php artisan view:clear
RUN php artisan route:clear
RUN php artisan storage:link

# Set proper permissions for storage and bootstrap/cache directories
RUN chown -R www-data:www-data .
RUN chown -R www-data:www-data storage bootstrap/cache
RUN chmod -R 775 storage bootstrap/cache

# Configure Apache Virtual Host
COPY docker/apache/vhost.conf /etc/apache2/sites-available/000-default.conf
RUN a2dissite 000-default
RUN a2ensite 000-default

# Expose port 80 for the web server
EXPOSE 80

CMD ["apache2-foreground"]