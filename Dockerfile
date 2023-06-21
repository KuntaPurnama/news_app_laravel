FROM php:8.1-fpm

RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

WORKDIR /app

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY . /app

#install composer
RUN composer install --no-interaction --optimize-autoloader


#Start the Laravel development server
CMD php artisan serve --host=0.0.0.0 --port=8000