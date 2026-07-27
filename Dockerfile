# ---------------------------------------------------------------------
# Image de production pour Render : PHP-FPM + Nginx en un seul conteneur
# ---------------------------------------------------------------------
FROM php:8.3-cli

RUN apt-get update && apt-get install -y \
    git unzip libpq-dev libzip-dev libonig-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql zip \
    && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && cp .env.example .env \
    && php artisan key:generate --force

RUN chmod -R 775 storage bootstrap/cache

EXPOSE 10000

# Render fournit $PORT dynamiquement ; on migre puis on lance le serveur intégré.
CMD php artisan migrate --force && \
    php artisan config:cache && \
    php artisan route:cache && \
    php artisan storage:link && \
    php artisan serve --host=0.0.0.0 --port=${PORT:-10000}
