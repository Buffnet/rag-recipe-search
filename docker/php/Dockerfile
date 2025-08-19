FROM php:8.3-fpm

# System deps
RUN apt-get update && apt-get install -y         git curl zip unzip libpq-dev libicu-dev libonig-dev libzip-dev libxml2-dev         && docker-php-ext-install pdo pdo_pgsql intl zip bcmath soap         && pecl install xdebug         && docker-php-ext-enable xdebug         && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Permissions for storage/bootstrap (created by init script after create-project)
RUN usermod -u 1000 www-data && groupmod -g 1000 www-data
