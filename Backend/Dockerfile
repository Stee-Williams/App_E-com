FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends $PHPIZE_DEPS libicu-dev libonig-dev libpq-dev libjpeg62-turbo-dev libpng-dev libwebp-dev curl \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install gd intl mbstring opcache pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --no-scripts

COPY . .
RUN composer dump-autoload --classmap-authoritative --no-dev --no-scripts \
    && a2enmod rewrite headers \
    && mkdir -p var/cache var/log public/uploads/produits \
    && chown -R www-data:www-data var public/uploads

COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf

ENV APP_ENV=prod
ENV APP_DEBUG=0

EXPOSE 80
CMD ["apache2-foreground"]
