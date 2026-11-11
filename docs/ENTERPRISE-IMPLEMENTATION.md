# Moto Manager — Architecture Enterprise (extension additive)

## 1. Principes de compatibilité
- Les routes API Flutter existantes ne sont ni renommées ni supprimées.
- Sanctum reste le mécanisme d'authentification.
- Les nouvelles routes sont sous `/api/enterprise` ou `/api/automation`.
- Les nouvelles migrations ajoutent des colonnes/tables; elles ne réécrivent pas les données métier existantes.
- Les calculs financiers restent dans Laravel. L'IA ne peut ni modifier les versements ni valider les absences.

## 2. Architecture
Flutter -> Laravel 11 API -> MySQL.
Laravel Scheduler déclenche les scans.
Redis + Laravel Queue exécutent les tâches asynchrones.
n8n orchestre les événements externes (WhatsApp, e-mail, systèmes tiers).
Ollama est facultatif et fournit un LLM local.
Dashboard Flutter consomme `/api/enterprise/dashboard`.

## 3. Installation
1. Sauvegarder la base de données et le dossier `storage`.
2. Fusionner ce dossier dans le dépôt existant (ou utiliser directement le ZIP Enterprise).
3. Copier `.env.example` vers `.env` si nécessaire et renseigner les secrets.
4. Installer/mettre à jour les dépendances avec `composer install`.
5. Exécuter `php artisan migrate`.
6. Démarrer Redis avec `docker compose -f docker-compose.yml -f docker-compose.enterprise.yml --profile redis --profile automation up -d redis n8n`, puis lancer le worker: `php artisan queue:work --queue=automation,default`. Dans `.env`, définir `QUEUE_CONNECTION=redis`, `REDIS_HOST=redis` et l'image Docker Enterprise active l'extension PHP Redis (phpredis).
7. Démarrer le scheduler: `php artisan schedule:work` en développement; en production, configurer le cron Laravel.
8. Démarrer n8n: `docker compose -f docker-compose.yml -f docker-compose.enterprise.yml --profile automation up -d n8n`. Pour Ollama: ajouter `--profile ai`.
9. Importer les fichiers JSON du dossier `n8n/workflows` dans l'interface n8n.
10. Configurer les variables d'environnement et credentials SMTP/WhatsApp dans n8n. Activer les workflows après test.

## 4. Variables .env
- `AUTOMATION_WEBHOOK_SECRET`: secret aléatoire long partagé avec n8n.
- `N8N_WEBHOOK_URL`: URL du webhook d'événement n8n.
- `AI_ENABLED=true`, `OLLAMA_BASE_URL`, `OLLAMA_MODEL`: active l'assistant local uniquement après installation du modèle.
- WhatsApp: `WHATSAPP_ENABLED`, `WHATSAPP_PHONE_NUMBER_ID`, `WHATSAPP_ACCESS_TOKEN`.
Ne jamais committer les vrais secrets dans Git. En production, utiliser HTTPS et un gestionnaire de secrets.

## 5. API ajoutées
### Authentification requise (Sanctum + rôle administrateur)
- `GET /api/enterprise/dashboard` — indicateurs d'automatisation, livraisons et insights.
- `POST /api/enterprise/ai/assistant` — body `{"question":"Quels sont les retards à traiter ?"}`.

### Machine-to-machine
- `POST /api/automation/events` — en-tête `X-Automation-Secret`, body:
  `{"event":"automation.failed","idempotency_key":"...","occurred_at":"ISO-8601","data":{}}`.
- Cet endpoint ne permet pas d'exécuter une commande arbitraire. Il journalise les événements reçus.

Toutes les routes métier déjà consommées par Flutter restent inchangées.

## 6. Commandes et planification
- `php artisan notifications:versements` (existant; conservé).
- `php artisan fleet:scan-maintenance` (nouveau; tous les jours à 07:00).
- `php artisan fleet:generate-insights` (nouveau; tous les jours à 07:15).
Les commandes sont idempotentes autant que possible. La maintenance nécessite de renseigner le kilométrage et de créer des plans dans `maintenance_plans`.

## 7. Workflows n8n fournis
- `01-router-notifications.json`: webhook entrant, routage d'événements; nodes WhatsApp/e-mail laissés désactivés jusqu'à configuration des credentials et du mapping des destinataires.
- `02-daily-report-trigger.json`: déclencheur quotidien qui envoie une demande d'événement à l'API.
- `03-error-monitoring.json`: callback de journalisation des erreurs.
Les workflows sont des modèles à importer et à configurer. Le backend fourni ne prétend pas envoyer WhatsApp ou les rapports tant que les credentials et les traitements correspondants ne sont pas configurés.

## 8. IA : limites et garde-fous
- L'assistant Ollama reçoit uniquement des compteurs agrégés dans cette première version.
- Les prédictions livrées sont des indicateurs/règles heuristiques, pas un modèle ML entraîné.
- Les anomalies doivent être examinées par un administrateur.
- Toute action sensible (suspension, retenue, modification de paiement, envoi massif) exige une confirmation humaine.
- Pour des prédictions statistiques robustes, accumuler un historique propre et définir une période d'évaluation.

## 9. Étapes de production à terminer
- Renseigner les plans d'entretien initiaux par modèle de moto.
- Ajouter un endpoint validé pour la saisie du kilométrage (avec historique/audit).
- Implémenter les templates et le consentement des destinataires WhatsApp.
- Configurer SMTP et les credentials n8n.
- Ajouter tests Feature sur rôles, signature webhook, migrations et idempotence.
- Configurer sauvegardes, HTTPS, monitoring, rotation des secrets et alertes d'échec.
