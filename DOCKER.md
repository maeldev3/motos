# 🐳 Docker — FleetMoto API

Cette configuration permet de lancer l'API Laravel de gestion de flotte de
motos dans un environnement conteneurisé, propre, reproductible et testable
en local avant un déploiement en ligne.

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
.env.docker.example       # Modèle de .env prêt pour Docker
.dockerignore
Makefile
```

## Architecture

| Service      | Rôle                                       | Actif par défaut |
|--------------|---------------------------------------------|:-----------------:|
| `app`        | PHP-FPM 8.3 (Laravel)                        | ✅ |
| `nginx`      | Serveur web, expose le port `7490`           | ✅ |
| `mysql`      | Base de données MySQL 8.4                    | ✅ |
| `phpmyadmin` | Interface web d'admin MySQL, port `7489`     | ✅ |
| `mailpit`    | Capture des emails envoyés (dev), port `1089`| ⛔ profile `mail` |
| `redis`      | Cache / queue                                | ⛔ profile `redis` |
| `queue`      | Worker `php artisan queue:work`              | ⛔ profile `queue` |
| `postgres`   | Alternative Postgres locale (offline)        | ⛔ profile `local-postgres` |

Le stack `app + nginx + mysql + phpmyadmin` démarre **automatiquement**
avec un simple `docker compose up`, pour un environnement de test local
fonctionnel sans rien configurer sur ta machine hôte. `app` attend que
`mysql` soit réellement prêt (healthcheck) avant de lancer les migrations.

## Démarrage rapide (local)

```bash
# 1. Utiliser le .env prêt pour Docker (DB_HOST=mysql, pas 127.0.0.1 !)
cp .env.docker.example .env

# 2. Lancer tout le stack
docker compose up -d --build

# 3. Vérifier que tout tourne
docker compose ps
```

- API : http://localhost:7490
- phpMyAdmin : http://localhost:7489 (user/password = `DB_USERNAME`/`DB_PASSWORD` du `.env`)

## ⚠️ Erreur fréquente : 502 Bad Gateway

Si nginx renvoie 502, c'est presque toujours parce que `app` a crashé au
démarrage (souvent une connexion DB impossible pendant `php artisan
migrate`). Vérifie :

```bash
docker compose logs app
docker compose ps        # "app" doit être "Up", pas en boucle de redémarrage
```

**Cause n°1** : `DB_HOST=127.0.0.1` dans le `.env`. À l'intérieur d'un
conteneur, `127.0.0.1` désigne le conteneur lui-même — jamais ta machine
hôte ni un autre conteneur. Utilise `DB_HOST=mysql` (nom du service Docker)
pour te connecter au conteneur MySQL du même stack.

## Emails en local (Mailpit)

```bash
docker compose --profile mail up -d
# ou : make up-mail
```

Dans `.env` : `MAIL_HOST=mailpit`, `MAIL_PORT=1025`. Tous les emails
envoyés par Laravel sont capturés (jamais réellement envoyés) et visibles
sur **http://localhost:1089**.

## Basculer vers Neon (PostgreSQL cloud) au lieu de MySQL

Utile pour tester exactement la config que tu utiliseras en ligne :

```env
DB_CONNECTION=pgsql
DATABASE_URL=postgresql://neondb_owner:xxxx@ep-xxxx.neon.tech/neondb?sslmode=require
```

Tu peux alors éteindre `mysql`/`phpmyadmin` (`docker compose stop mysql
phpmyadmin`) : Laravel se connectera directement à Neon, en local comme
en production, sans rien changer d'autre.

## Passage en ligne (production)

Les points à adapter au moment du déploiement :

1. **Base de données** : soit tu gardes le conteneur `mysql` sur ton VPS
   (avec un volume persistant, déjà prévu), soit tu bascules sur une base
   managée (Neon, PlanetScale, RDS…) en ne changeant que `DB_CONNECTION` /
   `DB_HOST` / `DATABASE_URL`.
2. **`.env`** : mets `APP_ENV=production`, `APP_DEBUG=false`, régénère
   `APP_KEY`, et ne commite jamais ce fichier.
3. **`docker-compose.yml`** : retire `phpmyadmin` de l'exposition publique
   (ou mets-le derrière une authentification / VPN) — il ne devrait pas
   être accessible depuis Internet sans protection.
4. **`nginx`** : ajoute HTTPS (Let's Encrypt / reverse proxy Traefik ou
   Caddy devant) plutôt que d'exposer le port 80/7490 brut.

## Commandes utiles (via Makefile)

```bash
make up             # démarre app + nginx + mysql + phpmyadmin
make up-mail        # + Mailpit
make up-full        # + mail + redis + queue + postgres alternatif
make bash           # shell dans le conteneur app
make migrate        # php artisan migrate
make fresh          # migrate:fresh --seed
make logs           # logs en direct du conteneur app
make test           # php artisan test
```

## Points clés de la config

- **Build multi-stage** : les dépendances Composer sont installées dans une
  image dédiée puis copiées dans l'image finale (image plus légère, pas de
  Composer en prod).
- **Extensions PHP** : `pdo_pgsql`/`pgsql` (Neon), `pdo_mysql` (MySQL),
  `bcmath`, `gd`, `intl`, `opcache`… alignées sur les besoins du projet.
- **Healthcheck MySQL + `depends_on: condition: service_healthy`** : `app`
  ne démarre ses migrations qu'une fois MySQL réellement prêt à accepter
  des connexions — évite le crash-loop / 502 au premier démarrage.
- **Profils Docker Compose** : les services optionnels (`mailpit`, `redis`,
  `queue`, `postgres`) ne tournent pas par défaut, évitant de gaspiller des
  ressources quand ils ne sont pas utilisés.