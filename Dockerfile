FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    git unzip curl libicu-dev libpq-dev libzip-dev zip libonig-dev libxml2-dev \
    libpng-dev libjpeg-dev libfreetype6-dev libcurl4-openssl-dev \
    libssl-dev libxslt-dev libffi-dev \
    && docker-php-ext-install pdo pdo_mysql intl zip opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer


RUN curl -sS https://get.symfony.com/cli/installer | bash && mv /root/.symfony*/bin/symfony /usr/local/bin/symfony

WORKDIR /var/www/html

COPY composer.json composer.lock ./


RUN composer install --no-interaction --no-scripts --prefer-dist


COPY . .


RUN composer install --no-interaction

RUN mkdir -p var logs public && chown -R www-data:www-data var logs public


EXPOSE 9000

CMD ["php-fpm"]
