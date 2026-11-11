# Import des workflows n8n
1. Ouvrir n8n -> Workflows -> Import from File.
2. Importer chaque JSON.
3. Définir les variables n8n `MOTO_API_URL`, `AUTOMATION_WEBHOOK_SECRET`, `WHATSAPP_API_VERSION`, `WHATSAPP_PHONE_NUMBER_ID`, `WHATSAPP_ACCESS_TOKEN`, `SMTP_FROM_EMAIL`.
4. Configurer les credentials SMTP dans le node Email.
5. Adapter le mapping des numéros/e-mails depuis le payload Laravel avant activation.
6. Tester d'abord avec un numéro/e-mail de test.

Les nodes WhatsApp et e-mail sont volontairement désactivés tant que les secrets et destinataires ne sont pas configurés.

Sécurité: sur le node webhook d'entrée, associer un credential Header Auth dont le nom d'en-tête est `X-Automation-Secret` et la valeur est identique à `AUTOMATION_WEBHOOK_SECRET`. Ne pas activer le workflow avant cette configuration.
