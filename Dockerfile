FROM php:8.3-cli

# Install mysqli and pdo_mysql
RUN docker-php-ext-install mysqli pdo_mysql

# Copy app files
COPY . /app/

WORKDIR /app

EXPOSE 80

CMD ["php", "-S", "0.0.0.0:80", "-t", "/app"]
