FROM php:7.4-fpm

RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libpng-dev \
  && docker-php-ext-install pdo_mysql zip gd

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

RUN composer install --no-dev --optimize-autoloader
RUN ./vendor/bin/rr get-binary

CMD ["./vendor/bin/rr", "serve", "-c", ".rr.yaml"]