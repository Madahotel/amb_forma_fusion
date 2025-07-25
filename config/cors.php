<?php

return [
    'paths' => [
        'api/*', 
        'sanctum/csrf-cookie',
        'export-transactions' // Ajoutez cette ligne
    ],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:3000',
        'https://votre-frontend.com' // Remplacez par votre domaine de prod
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => [
        'Content-Type',
        'Authorization',
        'X-Requested-With',
        'X-CSRF-TOKEN'
    ],

    'exposed_headers' => [
        'Content-Disposition' // Essentiel pour les téléchargements
    ],

    'max_age' => 86400, // 24 heures

    'supports_credentials' => true, // Doit être true pour les cookies/auth
];