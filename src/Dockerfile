FROM php:8.3-fpm-alpine

RUN apk add --no-cache $PHPIZE_DEPS icu-dev libzip-dev oniguruma-dev git bash curl \
    && docker-php-ext-install pdo_mysql bcmath intl opcache zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS

WORKDIR /var/www/html
CMD ["php-fpm"]

