<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | IMPORTANT: allowed_origins cannot be ['*'] when supports_credentials is
    | true — browsers reject credentialed requests (cookies/sessions) from a
    | wildcard origin. Always list exact allowed origins.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'admin/*', 'app/*', 'up'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_unique(array_filter([
        env('ADMIN_URL', 'http://localhost:3000'),
        env('FRONTEND_URL', 'http://localhost:4200'),
        'http://localhost:3000',
        'http://127.0.0.1:3000',
        'http://localhost:4200',
        'http://127.0.0.1:4200',
    ]))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => ['Content-Disposition'],

    'max_age' => 0,

    'supports_credentials' => true,

];
