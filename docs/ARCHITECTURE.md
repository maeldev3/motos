# Architecture cible et règles de développement

## 1. Principes

Le projet suit une architecture Laravel en couches, pragmatique et progressive :

- **Routes** : déclarent les URI, verbes HTTP et middlewares ; aucune règle métier.
- **Controllers** : adaptent HTTP au cas d'utilisation ; ils orchestrent et retournent une réponse.
- **Form Requests** (`app/Http/Requests`) : valident les entrées et portent les autorisations liées à la requête.
- **Services / Actions** (`app/Services`, `app/Actions`) : contiennent les règles métier, les transactions et les opérations multi-modèles.
- **Models** : relations Eloquent, casts et scopes ; éviter d'y placer des traitements HTTP.
- **API Resources** (`app/Http/Resources`) : stabilisent le contrat JSON et empêchent l'exposition accidentelle de colonnes.
- **Policies** (`app/Policies`) : vérifient l'accès à une ressource précise.
- **Jobs / Events / Listeners** : isolent les notifications et traitements longs.
- **Tests Feature** : couvrent les contrats HTTP, les rôles, les cas limites et les règles financières.

## 2. Flux d'une requête

```text
Client mobile / Web
       |
       v
Route API -> Throttle -> Sanctum -> Role / Policy
       |
       v
FormRequest (validation + autorisation)
       |
       v
Controller (orchestration HTTP)
       |
       v
Service / Action (transaction métier)
       |
       v
Eloquent Model <-> Database
       |
       v
API Resource -> JSON response
```

## 3. Conventions REST

- Collection : `GET /ressources`, création : `POST /ressources`.
- Détail : `GET /ressources/{id}`.
- Mise à jour : `PATCH /ressources/{id}` (partielle) ou `PUT` (complète).
- Suppression : `DELETE /ressources/{id}`.
- Actions métier explicites : `/motos/{moto}/desactiver`, `/avances/{avance}/rembourser`.
- Utiliser les codes HTTP correctement : 200, 201, 204 si aucune réponse, 401, 403, 404, 409, 422, 429.
- Ajouter `?page=1&per_page=20` aux listes, avec une limite maximale de 100.
- Dates au format ISO-8601 (`YYYY-MM-DD` pour les dates métier).
- Montants stockés en `DECIMAL`, jamais en flottants binaires pour les opérations financières.

## 4. Contrat JSON

Le contrat existant `success/message/data` est conservé pour ne pas casser l'application Flutter. Toute évolution de ce contrat doit être versionnée et accompagnée d'une migration du client.

Exemple de succès :

```json
{
  "success": true,
  "message": "Moto récupérée",
  "data": {
    "id": 12,
    "immatriculation": "1234 TBA"
  }
}
```

Exemple de validation :

```json
{
  "success": false,
  "message": "Validation échouée",
  "errors": {
    "immatriculation": ["Cette immatriculation est déjà utilisée."]
  }
}
```

## 5. Sécurité

- Sanctum Bearer Token pour les clients mobiles.
- Création de compte réservée à l'administrateur.
- Limitation de tentatives sur la connexion.
- Mot de passe robuste et hashé.
- Gate `viewApiDocs` : documentation en production réservée à l'administrateur.
- Ne jamais exposer `password`, `remember_token`, jetons ou secrets.
- Appliquer des Policies sur les ressources appartenant à un utilisateur/tenant.
- Journaliser les actions sensibles sans enregistrer les mots de passe ni jetons.
- Configurer HTTPS, `APP_DEBUG=false`, CORS minimal et secrets par environnement.

## 6. Priorités de refactorisation

La correction présente ajoute la documentation OpenAPI, verrouille l'inscription, limite les tentatives de connexion et corrige le cache de détail moto. Pour une refonte complète, procéder par domaine sans casser le client :

1. Extraire les règles de validation de chaque contrôleur vers des Form Requests.
2. Introduire une API Resource par agrégat (Moto, Conducteur, Versement, etc.).
3. Déplacer les opérations financières et d'affectation vers des Services/Actions avec `DB::transaction()`.
4. Ajouter Policies et tests de permissions pour chaque rôle.
5. Standardiser les filtres, tris, pagination et erreurs.
6. Ajouter des tests de concurrence/idempotence pour versements et remboursements.
7. Introduire `/api/v1` uniquement lors d'une évolution incompatible, en conservant temporairement `/api`.

## 7. Contrôles avant production

```bash
composer validate
php artisan route:list --path=api
php artisan test
vendor/bin/pint --test
php artisan scramble:export
```

La migration doit être testée sur une copie de la base et les sauvegardes vérifiées avant tout déploiement.
