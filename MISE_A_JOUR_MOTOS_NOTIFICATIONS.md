# Mise à jour motos, périodicité et notifications

## Affectations mises à jour
- Njaka : PETER 23, blanche — 0001 AAA — versement mensuel 600 000 Ar.
- Jese : PETER 7, noire — 0002 BBA — versement mensuel 600 000 Ar.
- Fihorenana (Beloha) : PETER 7, noire — 0003 CCC — versement mensuel 600 000 Ar.
- Manana : PETER 7, rouge — 0004 DDD — versement hebdomadaire 170 000 Ar.

Les immatriculations sont des identifiants techniques repris du projet existant. Remplacez-les par les vraies immatriculations si nécessaire. Le téléphone de Manana est une valeur provisoire à remplacer.

## Notifications ajoutées
API protégée par Sanctum :
- `GET /api/notifications?non_lues=1` : liste paginée et nombre de notifications non lues.
- `PATCH /api/notifications/{id}/lire` : marquer une notification comme lue.
- `PATCH /api/notifications/lire-tout` : marquer toutes les notifications comme lues.
- `DELETE /api/notifications/{id}` : supprimer une notification.

La commande `php artisan notifications:versements` vérifie les prochaines échéances hebdomadaires et mensuelles et crée une alerte sans doublon pour la journée. Elle est planifiée à 08:00 chaque jour dans `routes/console.php`. En production, configurez le cron Laravel habituel :
`* * * * * cd /chemin/vers/moto-api && php artisan schedule:run >> /dev/null 2>&1`

## Installation sur une base existante
1. Sauvegardez la base.
2. Déployez les fichiers.
3. Lancez :
   ```bash
   composer install --no-dev --optimize-autoloader
   php artisan migrate
   php artisan db:seed --class=DatabaseSeeder
   php artisan optimize:clear
   php artisan notifications:versements
   ```
La migration ajoute les champs de périodicité. Le seeder met à jour les quatre motos et conducteurs ciblés et les affectations correspondantes. Il ne faut pas exécuter `migrate:fresh` sur une base contenant des données à conserver.

## Historique de Manana
Les quatre versements visibles sont importés comme opérations hebdomadaires : 170 000 Ar (18/10/2026), 85 000 Ar (20/10/2026), 170 000 Ar (27/10/2026), 170 000 Ar (07/11/2026). Le paiement de 85 000 Ar est marqué comme partiel par rapport à l'échéance hebdomadaire de 170 000 Ar.
