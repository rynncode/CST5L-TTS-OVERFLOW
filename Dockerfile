FROM php:8.3-apache

# Fix MPM conflict — disable event, enable prefork
RUN a2dismod mpm_event mpm_worker 2>/dev/null || true \
    && a2enmod mpm_prefork

# Install mysqli and pdo_mysql
RUN docker-php-ext-install mysqli pdo_mysql

# Enable mod_rewrite
RUN a2enmod rewrite

# Copy app files
COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
