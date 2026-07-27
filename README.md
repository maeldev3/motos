# Moto Manager API

API Laravel professionnelle pour la gestion d'une flotte de motos/voitures, des conducteurs, des versements, dépenses, réparations et finances — basée sur le cahier des charges fourni.

## 1. Structure du projet

```
moto-api/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/   → AuthController, MotoController, ConducteurController,
│   │   │                        AffectationController, VersementController, AbsenceController,
│   │   │                        AvanceController, ReparationController, DepenseController,
│   │   │                        DashboardController, RapportController
│   │   └── Middleware/CheckRole.php   → contrôle des rôles (administrateur, gestionnaire, comptable, consultation)
│   └── Models/                → User, Moto, Conducteur, Affectation, Versement, Absence,
│                                 Avance, AvanceRemboursement, Reparation, Depense, Alerte
├── database/
│   ├── migrations/            → toutes les tables du cahier des charges
│   ├── seeders/                → compte administrateur par défaut
│   └── factories/
├── routes/api.php             → toutes les routes API (protégées par Sanctum)
├── resources/views/rapports/  → vue PDF (dompdf)
├── tests/Feature/MotoTest.php → exemple de tests automatisés
├── tests_api/                 → requêtes de test manuelles (curl + fichier .http)
├── flutter_example/           → exemple de client Flutter
├── Dockerfile                 → image de déploiement (Render)
├── render.yaml                → blueprint Render
└── .env.example
```

## 2. Installation locale

Prérequis : PHP 8.2+, Composer, extension `pdo_pgsql`.

```bash
cd moto-api
composer install
cp .env.example .env
php artisan key:generate
```

Configurez votre base de données dans `.env` (voir section Neon ci-dessous), puis :

```bash
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

L'API est disponible sur `http://localhost:8000/api`.

Compte administrateur créé par le seeder :
- email : `admin@moto-api.com`
- mot de passe : `password123`

⚠️ Changez ce mot de passe en production.

## 3. Base de données : MySQL en local, Neon (PostgreSQL) en alternative

Le projet supporte **deux connexions** dans `config/database.php` : `mysql` (dev local) et `pgsql` (Neon, prod/staging). Vous basculez de l'une à l'autre uniquement via `.env` — aucun changement de code n'est nécessaire.

### a) MySQL en local (configuration active par défaut dans `.env.example`)

1. Créez la base et l'utilisateur (adaptez si vous utilisez déjà un compte) :
   ```bash
   mysql -u root -p -e "CREATE DATABASE moto_api CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   mysql -u root -p -e "CREATE USER 'ismael'@'localhost' IDENTIFIED BY 'Dev@2026Mysql!';"
   mysql -u root -p -e "GRANT ALL PRIVILEGES ON moto_api.* TO 'ismael'@'localhost'; FLUSH PRIVILEGES;"
   ```
2. `.env` (déjà pré-rempli dans `.env.example`) :
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=moto_api
   DB_USERNAME=ismael
   DB_PASSWORD=Dev@2026Mysql!
   ```
   Comptes disponibles en local (au choix) :
   | Utilisateur | Mot de passe | Usage |
   |---|---|---|
   | `root` | `Root@2026!` | Admin serveur MySQL |
   | `ismael` | `Dev@2026Mysql!` | Développeur (utilisé par défaut) |
   | `admin` | `Admin@2026!` | Compte applicatif |

   ⚠️ Ces mots de passe sont fournis pour le développement local uniquement — ne les réutilisez jamais en production, et ne committez pas le `.env` réel (seul `.env.example` doit être versionné).

3. Migrations :
   ```bash
   php artisan migrate --seed
   ```

### b) Neon (PostgreSQL gratuit, pour la prod/staging — ex. Render)

1. Créez un compte sur https://neon.tech et un nouveau projet.
2. Dans le dashboard Neon, copiez la **Connection string** (bouton "Connect"), du type :
   ```
   postgresql://neondb_owner:VOTRE_MDP@ep-xxxx-xxxx.eu-central-1.aws.neon.tech/neondb?sslmode=require
   ```
3. Dans `.env`, **commentez le bloc MySQL** et **décommentez le bloc Neon** (déjà présent en commentaire dans `.env.example`) :

   **Option A — variable unique (recommandée) :**
   ```env
   DATABASE_URL=postgresql://neondb_owner:VOTRE_MDP@ep-xxxx.neon.tech/neondb?sslmode=require
   ```

   **Option B — variables séparées :**
   ```env
   DB_CONNECTION=pgsql
   DB_HOST=ep-xxxx-xxxx.eu-central-1.aws.neon.tech
   DB_PORT=5432
   DB_DATABASE=neondb
   DB_USERNAME=neondb_owner
   DB_PASSWORD=VOTRE_MDP
   DB_SSLMODE=require
   ```

4. Lancez les migrations :
   ```bash
   php artisan migrate --seed
   ```

`config/database.php` utilise `DATABASE_URL` en priorité si elle est définie (que ce soit pour `mysql` ou `pgsql`).

## 4. Déploiement sur Render

Le projet contient un `Dockerfile` et un `render.yaml` prêts à l'emploi.

### Étapes :

1. Poussez le projet (dézippé) sur un dépôt GitHub.
2. Sur https://render.com → **New +** → **Blueprint** → sélectionnez le dépôt (Render détecte `render.yaml` automatiquement).
   - Ou bien **New +** → **Web Service** → **Environment: Docker**.
3. Renseignez la variable d'environnement `DATABASE_URL` avec la chaîne de connexion Neon (dans l'onglet *Environment* du service Render).
4. Render construit l'image (`Dockerfile`), exécute automatiquement au démarrage :
   ```
   php artisan migrate --force
   php artisan config:cache
   php artisan route:cache
   php artisan storage:link
   php artisan serve --host=0.0.0.0 --port=$PORT
   ```
5. Une fois déployé, votre API sera accessible à une URL du type :
   ```
   https://moto-manager-api.onrender.com/api
   ```

> Le plan gratuit Render met le service en veille après inactivité (le premier appel après une pause peut prendre ~30-60s).

## 5. Rôles et sécurité

Authentification par token (Laravel Sanctum). 4 rôles : `administrateur`, `gestionnaire`, `comptable`, `consultation`.

- `POST /api/auth/login` → renvoie un `token` à utiliser en en-tête `Authorization: Bearer {token}`.
- Certaines routes peuvent être restreintes via le middleware `role:administrateur,gestionnaire`.

## 6. Tester l'API

### a) Avec le script curl fourni
```bash
cd tests_api
./test_curl.sh http://localhost:8000/api
```

### b) Avec le fichier `.http` (extension "REST Client" VSCode ou import Postman)
Voir `tests_api/requetes_api.http`.

### c) Tests automatisés PHPUnit
```bash
php artisan test
# ou
vendor/bin/phpunit
```
Exemple fourni : `tests/Feature/MotoTest.php` (création, doublons, bilan financier).

## 7. Consommer l'API depuis Flutter

Voir `flutter_example/api_service.dart` (service HTTP complet : login, motos, versements, dashboard)
et `flutter_example/main_example.dart` (écran Flutter minimal affichant la liste des motos).

Ajoutez dans `pubspec.yaml` :
```yaml
dependencies:
  http: ^1.2.0
```

Remplacez `baseUrl` dans `api_service.dart` par l'URL Render de votre API déployée.

## 8. Principaux endpoints

| Méthode | Route | Description |
|---|---|---|
| POST | /api/auth/login | Connexion |
| GET | /api/auth/me | Utilisateur connecté |
| GET/POST | /api/motos | Liste / création moto |
| PUT/DELETE | /api/motos/{id} | Modification / suppression |
| POST | /api/motos/{id}/desactiver, /reactiver | Statut moto |
| GET | /api/motos/{id}/finances | Revenus/dépenses/bénéfices |
| GET/POST | /api/conducteurs | Liste / création conducteur |
| POST | /api/conducteurs/{id}/affecter-moto | Affectation + historique |
| GET/POST | /api/versements | Versements |
| GET | /api/versements-resume | Résumé dette/retards |
| GET/POST | /api/absences | Absences + retenues auto |
| GET/POST | /api/avances | Avances/provisions |
| POST | /api/avances/{id}/rembourser | Remboursement |
| GET/POST | /api/reparations | Réparations |
| GET/POST | /api/depenses | Dépenses |
| GET | /api/depenses-par-categorie | Répartition dépenses |
| GET | /api/dashboard | Tableau de bord temps réel |
| GET | /api/dashboard/graphiques | Données graphiques |
| GET | /api/rapports/global, /moto/{id}, /export-pdf | Rapports |

## 9. Notes importantes

- Les packages PDF (`barryvdh/laravel-dompdf`) et Excel (`maatwebsite/excel`) sont déjà déclarés dans `composer.json` ; lancez `composer install` pour les récupérer.
- Le versement journalier par défaut pour les voitures (100 000 Ar/jour, selon votre cahier des charges) et mensuel pour les motos (600 000 Ar/mois) sont appliqués automatiquement à la création si non précisés, et restent configurables par véhicule.
- Le champ `search_path`/`sslmode` de `config/database.php` est déjà prêt pour Neon (SSL obligatoire).
- Pensez à sécuriser `APP_DEBUG=false` et un mot de passe admin fort avant la mise en production.
