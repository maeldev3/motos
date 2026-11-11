# Flutter — compatibilité Enterprise

Aucun endpoint existant n'est modifié. Les écrans actuels continuent à utiliser leurs routes habituelles.

## Nouvel écran « Centre d'intelligence »
Appeler `GET /api/enterprise/dashboard` avec le même Bearer token Sanctum.
La réponse respecte l'enveloppe habituelle:
`{ success: true, message: "OK", data: { ... } }`.

Afficher:
- `data.automations.runs_24h`, `failed_24h`, `last_runs`
- `data.notifications.pending`, `failed`, `sent_24h`
- `data.alerts.open`, `critical`
- `data.insights`

## Assistant IA
`POST /api/enterprise/ai/assistant`
Headers: `Authorization: Bearer <token>`, `Accept: application/json`.
Body: `{"question":"Quelles sont les priorités aujourd'hui ?"}`.
Réponse: `data.answer`, `data.context`, `data.interaction_id`.

## UX recommandée
- Ajouter l'écran comme nouvel onglet, sans déplacer les routes ou modèles actuels.
- Si réponse HTTP 503, afficher « Assistant temporairement indisponible » et proposer de réessayer.
- Les insights sont consultatifs; proposer « Marquer comme examiné » dans une future API dédiée.
- Ne jamais permettre au texte IA de déclencher directement une opération financière.
