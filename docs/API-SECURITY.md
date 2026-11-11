# Sécurité et mise en production

## Action immédiate : secrets présents dans l'archive d'origine

L'archive reçue contenait des fichiers `.env` et des identifiants de base de données réels dans des fichiers de configuration d'exemple. Ils ont été retirés de l'archive corrigée et remplacés par des valeurs `CHANGE_ME`.

**Considérez les mots de passe et la clé d'application qui figuraient dans l'archive comme compromis :**
1. Changez immédiatement le mot de passe de la base MySQL/PostgreSQL concernée.
2. Révoquez/remplacez les identifiants Neon si cette base est toujours utilisée.
3. Générez une nouvelle clé Laravel sur l'environnement approprié (`php artisan key:generate` en local ; variable `APP_KEY` sécurisée en production).
4. Révoquez les jetons API actifs si des utilisateurs réels ont pu être exposés.
5. Ne transmettez jamais `.env` dans un ZIP ou dépôt Git.

La rotation d'une `APP_KEY` en production peut invalider les données chiffrées et les sessions existantes : planifiez-la avant exécution.

## Configuration production minimale

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://votre-domaine`
- HTTPS/TLS activé
- `LOG_LEVEL=warning` (à ajuster)
- secrets injectés par le gestionnaire de variables du fournisseur
- sauvegardes chiffrées et restauration testée
- base de données accessible uniquement depuis les services autorisés
- documentation API accessible uniquement aux administrateurs
- CORS limité aux origines nécessaires
