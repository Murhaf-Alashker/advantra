<?php
return [
    'paths' => [
        'api/*',
        'broadcasting/auth', // مهم جدًا!
        'sanctum/csrf-cookie',
    ],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        '*' // عنوان Vite/React
    ],
    'allowed_headers' => ['*'], // أو اذكر Authorization, X-Requested-With, X-Socket-Id
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
