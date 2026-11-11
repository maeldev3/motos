# Moto Manager API

API REST de gestion de flotte et de suivi financier, construite avec **Laravel 11**, **PHP 8.2+**, **MySQL/PostgreSQL** et **Laravel Sanctum**.

## Fonctionnalités

- Authentification par jeton Bearer et gestion des rôles.
- Gestion des motos/véhicules et de leurs états.
- Gestion des conducteurs et historique des affectations.
- Versements, absences, avances et remboursements.
- Réparations et dépenses.
- Notifications/alertes.
- Tableau de bord et rapports PDF.

## Stack technique

| Couche | Technologie |
|---|---|
| Framework | Laravel 11 |
| Runtime | PHP 8.2+ |
| Authentification API | Laravel Sanctum |
| Base de données | MySQL 8+ ou PostgreSQL |
| Documentation | OpenAPI 3.1 via Dedoc Scramble |
| Tests | PHPUnit |
| Qualité de code | Laravel Pint |

## Installation locale

```bash
cp .env.example .env
composer update dedoc/scramble --with-all-dependencies
composer install
php artisan key:generate
```

Renseignez ensuite vos identifiants MySQL ou PostgreSQL dans `.env`. Créez la base de données puis lancez :

```bash
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

L'API est disponible sur `http://127.0.0.1:8000/api`.

## Documentation OpenAPI / Swagger UI

La documentation est générée automatiquement à partir des routes, règles de validation, contrôleurs et ressources Laravel par [Dedoc Scramble](https://scramble.dedoc.co/).

La dépendance est déclarée dans `composer.json`. La commande `composer update dedoc/scramble --with-all-dependencies` synchronise `composer.lock` avec cette nouvelle dépendance (à exécuter une seule fois après extraction, avant le premier `composer install`).

Après installation :

- Interface interactive : `http://127.0.0.1:8000/docs/api`
- Spécification OpenAPI JSON : `http://127.0.0.1:8000/docs/api.json`
- Export statique : `php artisan scramble:export`

La documentation est protégée en dehors de l'environnement local : seul un utilisateur authentifié avec le rôle `administrateur` peut consulter les pages de documentation.

**Important :** le fichier `composer.lock` fourni provient du projet d'origine et ne contient pas encore Scramble. Il faut donc synchroniser le verrouillage une seule fois avec `composer update dedoc/scramble --with-all-dependencies`, puis versionner le nouveau `composer.lock`. En CI/CD et sur les serveurs, utilisez ensuite uniquement `composer install`.

## Authentification

1. Envoyer `POST /api/auth/login` avec `email`, `password` et éventuellement `device_name`.
2. Récupérer `data.token`.
3. Envoyer le jeton dans chaque requête protégée :

```http
Authorization: Bearer VOTRE_JETON
Accept: application/json
```

4. Déconnexion : `POST /api/auth/logout` (révoque le jeton courant).

La création d'un compte (`POST /api/auth/register-utilisateur`) est réservée au rôle `administrateur`. Les mots de passe doivent comporter au moins 12 caractères, avec majuscules, minuscules, chiffres et symboles.

## Endpoints

Toutes les routes ci-dessous sont préfixées par `/api`. Sauf `POST /auth/login`, elles nécessitent un jeton Sanctum.

| Domaine | Méthodes et chemins |
|---|---|
| Authentification | `POST /auth/login`, `POST /auth/logout`, `GET /auth/me`, `POST /auth/register-utilisateur` (administrateur) |
| Motos | `GET/POST /motos`, `GET/PUT/PATCH/DELETE /motos/{moto}`, `POST /motos/{moto}/desactiver`, `POST /motos/{moto}/reactiver`, `GET /motos/{moto}/finances` |
| Conducteurs | `GET/POST /conducteurs`, `GET/PUT/PATCH/DELETE /conducteurs/{conducteur}`, `POST /conducteurs/{conducteur}/suspendre`, `POST /conducteurs/{conducteur}/reactiver`, `POST /conducteurs/{conducteur}/affecter-moto`, `GET /conducteurs/{conducteur}/historique-motos`, `GET /conducteurs/{conducteur}/versement` |
| Affectations | `GET /affectations`, `GET /affectations/{affectation}` |
| Versements | `GET/POST /versements`, `GET/PUT/PATCH/DELETE /versements/{versement}`, `GET /versements-resume` |
| Absences | `GET/POST /absences`, `GET/PUT/PATCH/DELETE /absences/{absence}` |
| Avances | `GET/POST /avances`, `GET/PUT/PATCH/DELETE /avances/{avance}`, `POST /avances/{avance}/rembourser` |
| Réparations | `GET/POST /reparations`, `GET/PUT/PATCH/DELETE /reparations/{reparation}` |
| Dépenses | `GET/POST /depenses`, `GET/PUT/PATCH/DELETE /depenses/{depense}`, `GET /depenses-par-categorie` |
| Alertes | `GET /alertes`, `POST /alertes/tout-lire`, `PATCH /alertes/{alerte}/lire`, `DELETE /alertes/{alerte}` |
| Dashboard | `GET /dashboard`, `/dashboard/conducteurs-evolution`, `/dashboard/motos-performance`, `/dashboard/versements-resume`, `/dashboard/modules`, `/dashboard/vehicules-actifs` |
| Rapports | `GET /rapports/global`, `GET /rapports/moto/{moto}`, `GET /rapports/export-pdf` |

Pour les paramètres détaillés, les types, exemples de payload et réponses, utilisez Swagger UI : les schémas sont générés depuis le code source.

## Format de réponse

Réponse applicative standard :

```json
{
  "success": true,
  "message": "Opération réussie",
  "data": {}
}
```

Erreur standard :

```json
{
  "success": false,
  "message": "Validation échouée",
  "errors": {
    "field": ["Le champ est obligatoire."]
  }
}
```

Codes HTTP courants : `200` succès, `201` création, `401` non authentifié, `403` accès interdit, `404` ressource introuvable, `422` validation échouée, `429` limite de requêtes atteinte, `500` erreur serveur.

## Qualité et tests

```bash
php artisan test
vendor/bin/pint --test
php artisan route:list --path=api
php artisan scramble:export
```

Avant mise en production, vérifiez aussi :

- `APP_DEBUG=false` et `APP_ENV=production`.
- HTTPS obligatoire.
- Secrets uniquement dans les variables d'environnement du serveur.
- Sauvegardes de base de données testées.
- Queue asynchrone et cache partagé adaptés au trafic.
- Limites de débit et journalisation sans données sensibles.
- Tests d'autorisation pour chaque rôle et chaque ressource.

## Structure recommandée

```text
app/
  Http/
    Controllers/Api/     # orchestration HTTP uniquement
    Requests/            # validation et autorisation des entrées
    Resources/           # contrats JSON stables
    Middleware/          # authentification, rôles, sécurité
  Models/                # relations et casts Eloquent
  Services/              # règles métier et transactions
  Actions/               # cas d'utilisation ciblés (si nécessaire)
  Policies/              # autorisation par ressource
  Jobs/                  # tâches longues / notifications
  Events/Listeners/      # événements métier
  Exceptions/            # exceptions métier
routes/api.php           # déclaration des endpoints
tests/Feature/           # tests HTTP et autorisation
```

Voir `docs/ARCHITECTURE.md` pour les règles de conception et la stratégie de migration progressive.

## Sécurité

Consultez `docs/API-SECURITY.md` avant tout déploiement : l'archive source contenait des secrets qui doivent être renouvelés.
