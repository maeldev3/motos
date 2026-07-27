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
    libfreetype6-dev \
    && rm -rf /var/lib/apt/lists/*

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

# Étape 1 : installer les dépendances SANS lancer les scripts artisan
# (artisan n'existe pas encore à ce stade)
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-scripts \
    --optimize-autoloader

# Étape 2 : copier le reste du projet (artisan y compris)
COPY . .

# Étape 3 : relancer l'autoload + les scripts maintenant qu'artisan existe
RUN composer dump-autoload --optimize --no-dev \
    && php artisan package:discover --ansi

RUN chmod -R 775 storage bootstrap/cache

EXPOSE 10000

CMD php artisan migrate --force && \
    php artisan config:cache && \
    php artisan route:cache && \
    php artisan serve --host=0.0.0.0 --port=${PORT}