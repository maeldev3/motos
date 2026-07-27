FROM php:8.4-cli

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    curl \
    libpq-dev \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev

RUN docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg

RUN docker-php-ext-install \
    gd \
    pdo \
    pdo_pgsql \
    pgsql \
    zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --optimize-autoloader

COPY . .

RUN cp .env.example .env || true

RUN php artisan key:generate --force || true

RUN chmod -R 775 storage bootstrap/cache

EXPOSE 10000

CMD php artisan migrate --force && \
    php artisan optimize:clear && \
    php artisan config:cache && \
    php artisan route:cache && \
    php artisan serve --host=0.0.0.0 --port=${PORT}