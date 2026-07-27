# ---------------------------------------------------------------------
# Laravel + PHP 8.4 + PostgreSQL (Neon) - Render
# ---------------------------------------------------------------------

    FROM php:8.4-cli

    # Installation des dépendances système
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
    
    # Configuration de GD
    RUN docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg
    
    # Installation des extensions PHP
    RUN docker-php-ext-install \
        pdo \
        pdo_pgsql \
        pgsql \
        zip \
        gd
    
    # Installation de Composer
    COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
    
    # Dossier de travail
    WORKDIR /var/www/html
    
    # Copie des fichiers
    COPY . .
    
    # Installation des dépendances Laravel
    RUN composer install \
        --no-dev \
        --prefer-dist \
        --optimize-autoloader \
        --no-interaction
    
    # Création du .env si absent
    RUN if [ ! -f .env ]; then cp .env.example .env; fi
    
    # Génération de la clé Laravel
    RUN php artisan key:generate --force
    
    # Permissions
    RUN chmod -R 775 storage bootstrap/cache
    
    # Port Render
    EXPOSE 10000
    
    # Lancement
    CMD php artisan migrate --force && \
        php artisan optimize:clear && \
        php artisan config:cache && \
        php artisan route:cache && \
        php artisan storage:link && \
        php artisan serve --host=0.0.0.0 --port=${PORT}