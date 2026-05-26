<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:5173,http://127.0.0.1:5173')),
    'allowed_origins_patterns' => [
        '/^https?:\\/\\/localhost(:\\d+)?$/',
        '/^https?:\\/\\/127\\.0\\.0\\.1(:\\d+)?$/',
        '/^https?:\\/\\/10\\.\\d+\\.\\d+\\.\\d+(:\\d+)?$/',
        '/^https?:\\/\\/192\\.168\\.\\d+\\.\\d+(:\\d+)?$/',
        '/^https?:\\/\\/172\\.(1[6-9]|2\\d|3[0-1])\\.\\d+\\.\\d+(:\\d+)?$/',
    ],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
