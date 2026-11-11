<?php

use Dedoc\Scramble\Http\Middleware\RestrictedDocsAccess;
use Dedoc\Scramble\SecurityDocumentation\MiddlewareAuthSecurityStrategy;
use Dedoc\Scramble\Support\Generator\SecurityScheme;

return [
    'api_path' => 'api',
    'api_domain' => null,
    'export_path' => 'api.json',
    'info' => [
        'version' => env('API_VERSION', '1.0.0'),
        'title' => env('APP_NAME', 'Moto Manager').' API',
        'description' => <<<'MARKDOWN'
# Moto Manager API

API REST de gestion de flotte : motos et véhicules, conducteurs, affectations,
versements, absences, avances, remboursements, réparations, dépenses,
tableaux de bord, rapports et notifications.

## Authentification
Les routes métier nécessitent un jeton Laravel Sanctum. Connectez-vous via
`POST /api/auth/login`, puis utilisez `Authorization: Bearer {token}`.

## Format des réponses
Les réponses applicatives utilisent généralement `success`, `message` et `data`.
Les erreurs de validation renvoient HTTP 422. Les routes protégées renvoient
HTTP 401 sans jeton valide et HTTP 403 lorsque le rôle est insuffisant.

## Bonnes pratiques
Utilisez HTTPS, conservez les jetons côté client de manière sécurisée et ne
commitez jamais les fichiers `.env`.
MARKDOWN,
    ],
    'servers' => null,
    'middleware' => [
        'web',
        RestrictedDocsAccess::class,
    ],
    'extensions' => [],
    'security_strategy' => [
        MiddlewareAuthSecurityStrategy::class,
        [
            'middleware' => ['auth', 'auth:*'],
            'scheme' => SecurityScheme::http('bearer'),
        ],
    ],
];
