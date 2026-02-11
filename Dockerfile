FROM php:8.2-fpm-alpine

RUN apk add --no-cache \
    mysql-client \
    mysql-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    icu-dev \
    linux-headers \
    && docker-php-ext-install pdo pdo_mysql zip intl opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./

RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY . .

RUN composer dump-autoload --optimize --no-dev \
    && composer run-script post-install-cmd --no-interaction || true

RUN mkdir -p var/cache var/log public/uploads \
    && chown -R www-data:www-data var public/uploads \
    && chmod -R 775 var public/uploads

RUN mkdir -p config/jwt \
    && chown -R www-data:www-data config/jwt \
    && chmod -R 755 config/jwt

COPY docker/php/php.ini /usr/local/etc/php/conf.d/app.ini

EXPOSE 9000

CMD ["php-fpm"]
