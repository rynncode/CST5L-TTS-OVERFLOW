FROM php:8.3-apache

# Install mysqli and pdo_mysql
RUN docker-php-ext-install mysqli pdo_mysql

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy app files
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
