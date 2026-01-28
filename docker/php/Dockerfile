FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
        bash \
        icu-dev \
        libzip-dev \
        oniguruma-dev \
        postgresql-dev \
        zlib-dev \
        $PHPIZE_DEPS \
    && docker-php-ext-install -j$(nproc) \
        bcmath \
        opcache \
        pcntl \
        pdo_pgsql \
        pgsql \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY ./app /var/www/html

RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

COPY ./docker/php/php.ini /usr/local/etc/php/conf.d/app.ini
COPY ./docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf

USER www-data
