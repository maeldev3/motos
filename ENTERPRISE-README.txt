MOTO API PROFESSIONNELLE — EXTENSION ENTERPRISE
================================================

Cette archive est une extension additive du ZIP source.

Nouveautés:
- Plans de maintenance par date et kilométrage.
- Kilométrage courant sur les motos.
- Sévérité, statut, déduplication et metadata des alertes.
- Journal de livraisons de notifications.
- Historique d'exécutions d'automatisations.
- Insights IA (heuristiques) et journal de l'assistant.
- API dashboard Enterprise et assistant Ollama.
- Webhook n8n protégé par secret.
- Commandes de scan de maintenance et génération d'insights.
- Modèles de workflows n8n exportés.
- Services Redis/n8n/Ollama ajoutés au Docker Compose.

IMPORTANT:
- Sauvegarder la base avant migration.
- Vérifier `.env`, secrets, SMTP, WhatsApp et Ollama avant activation.
- Les workflows JSON sont des modèles à configurer; les nodes d'envoi sont désactivés.
- Aucun envoi WhatsApp réel n'est effectué sans configuration Meta Business.
- Les règles métier et montants restent calculés par Laravel.
- Vérifier et exécuter les tests dans un environnement de staging avant production.
