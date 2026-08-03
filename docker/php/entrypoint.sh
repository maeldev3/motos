#!/bin/sh
set -e

echo "🚀 Démarrage du conteneur FleetMoto API..."

# Génère la clé d'application si elle est absente
if [ -z "${APP_KEY}" ]; then
    echo "🔑 Génération de la clé d'application..."
    php artisan key:generate --force
fi

# Cache la config/routes/vues uniquement en environnement de production
if [ "${APP_ENV}" = "production" ]; then
    echo "⚙️  Optimisation Laravel (config/route/view cache)..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
else
    php artisan config:clear
fi

echo "🗄️  Exécution des migrations..."
php artisan migrate --force

echo "🔗 Lien symbolique storage..."
php artisan storage:link || true

echo "✅ Application prête."

exec "$@"