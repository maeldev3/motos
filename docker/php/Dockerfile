# ============================================================
# Stage 1 — Installation des dépendances Composer
# ============================================================
FROM composer:2 AS vendor

WORKDIR /app

# On copie d'abord seulement composer.json/lock pour profiter
# du cache Docker (les deps ne sont réinstallées que si elles changent)
COPY composer.json composer.lock ./

RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --prefer-dist \
        --ignore-platform-reqs

COPY . .

RUN composer dump-autoload --optimize --no-dev

# ============================================================
# Stage 2 — Image finale PHP-FPM (production-ready)
# ============================================================
FROM php:8.3-fpm-alpine AS production

LABEL maintainer="FleetMoto Team"
LABEL description="API de gestion de flotte de motos (Laravel)"

WORKDIR /var/www/html

# Dépendances système + extensions PHP requises par le projet
# (pdo_pgsql/pgsql pour Neon, pdo_mysql pour le dev local MySQL)
RUN apk add --no-cache \
        bash \
        curl \
        git \
        freetype-dev \
        libpng-dev \
        libjpeg-turbo-dev \
        libzip-dev \
        libxml2-dev \
        oniguruma-dev \
        postgresql-dev \
        icu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        pdo_pgsql \
        pgsql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache

# Configuration PHP personnalisée (uploads, mémoire, opcache…)
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-custom.ini

# Code applicatif + vendor déjà générés au stage précédent
COPY --from=vendor /app /var/www/html

# Permissions correctes pour Laravel (storage / cache)
RUN chown -R www-data:www-data /var/www/html \
    && find storage bootstrap/cache -type d -exec chmod 775 {} \;

COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

USER www-data

EXPOSE 9000

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]