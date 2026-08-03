# 🐳 Docker — FleetMoto API

Cette configuration permet de lancer l'API Laravel de gestion de flotte de
motos dans un environnement conteneurisé, propre et reproductible.

## Structure

```
docker/
├── php/
│   ├── Dockerfile        # Build multi-stage (composer → php-fpm alpine)
│   ├── entrypoint.sh     # Migrations + cache au démarrage du conteneur
│   └── php.ini           # Config PHP (uploads, opcache, timezone)
└── nginx/
    └── conf.d/
        └── default.conf  # Vhost Nginx → PHP-FPM
docker-compose.yml
.dockerignore
Makefile
```

## Architecture

| Service      | Rôle                                     | Actif par défaut |
|--------------|-------------------------------------------|:-----------------:|
| `app`        | PHP-FPM 8.3 (Laravel)                     | ✅ |
| `nginx`      | Serveur web, expose le port `8000`        | ✅ |
| `postgres`   | Base de données locale Postgres (offline) | ⛔ profile `local-db` |
| `mysql`      | Base de données locale MySQL (offline)    | ⛔ profile `local-mysql` |
| `phpmyadmin` | Interface web d'admin MySQL, port `8080`  | ⛔ profile `phpmyadmin` |
| `redis`      | Cache / queue                             | ⛔ profile `redis` |
| `queue`      | Worker `php artisan queue:work`           | ⛔ profile `queue` |

Le projet est câblé par défaut sur **Neon (PostgreSQL cloud)** via
`DATABASE_URL` dans le `.env` : aucun conteneur de base de données n'est donc
requis pour démarrer. Les services `postgres` / `redis` / `queue` sont
fournis en option (profils Docker Compose) pour un développement 100% local.

## Démarrage rapide

```bash
# 1. Copier le .env (déjà fourni dans le projet)
cp .env.example .env

# 2. Lancer app + nginx (utilise Neon)
docker compose up -d --build

# L'API est disponible sur http://localhost:8000
```

## Mode 100% local (sans Neon)

```bash
docker compose --profile local-db --profile redis up -d
```

Puis, dans `.env` :
```
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=moto_api
DB_USERNAME=postgres
DB_PASSWORD=secret
CACHE_STORE=redis
QUEUE_CONNECTION=redis
REDIS_HOST=redis
```

## phpMyAdmin

Ton `.env` actif utilise `DB_CONNECTION=mysql` avec `DB_HOST=127.0.0.1`, ce
qui vise le MySQL installé sur ta machine **hôte** — pas un conteneur.
phpMyAdmin peut se brancher sur les deux :

**Option A — MySQL de l'hôte (celui déjà configuré dans `.env`)**

```bash
# Dans .env, ajouter :
PMA_HOST=host.docker.internal

docker compose --profile phpmyadmin up -d
# ou : make up-phpmyadmin
```

⚠️ `host.docker.internal` fonctionne nativement sur Docker Desktop
(Mac/Windows). Sur Linux, le mapping `extra_hosts: host-gateway` déjà
présent dans le compose s'en charge automatiquement (Docker Engine ≥ 20.10).

**Option B — MySQL conteneurisé (isolé, ne touche pas au MySQL de l'hôte)**

```bash
docker compose --profile local-mysql --profile phpmyadmin up -d
# ou : make up-mysql
```

Dans ce cas, mets aussi `DB_HOST=mysql` dans `.env` pour que Laravel (dans
le conteneur `app`) se connecte au conteneur MySQL plutôt qu'à l'hôte.

Interface disponible sur **http://localhost:8080** (identifiants : ceux de
`DB_USERNAME` / `DB_PASSWORD` dans `.env`, ou `root` / `MYSQL_ROOT_PASSWORD`
pour un accès complet).

## Commandes utiles (via Makefile)

```bash
make up             # démarre app + nginx
make up-full        # démarre tout (db locale + redis + queue)
make up-mysql       # + phpMyAdmin branché sur MySQL conteneurisé
make up-phpmyadmin  # + phpMyAdmin branché sur le MySQL de l'hôte
make bash           # shell dans le conteneur app
make migrate     # php artisan migrate
make fresh       # migrate:fresh --seed
make logs        # logs en direct du conteneur app
make test        # php artisan test
```

## Points clés de la config

- **Build multi-stage** : les dépendances Composer sont installées dans une
  image dédiée puis copiées dans l'image finale (image plus légère, pas de
  Composer en prod).
- **Extensions PHP** : `pdo_pgsql`/`pgsql` (Neon), `pdo_mysql` (dev local),
  `bcmath`, `gd`, `intl`, `opcache`… alignées sur les besoins du projet.
- **Entrypoint idempotent** : génère la clé d'app si absente, joue les
  migrations, régénère les caches en production.
- **Profils Docker Compose** : les services optionnels (`postgres`, `redis`,
  `queue`) ne tournent pas par défaut, évitant de gaspiller des ressources
  quand ils ne sont pas utilisés.