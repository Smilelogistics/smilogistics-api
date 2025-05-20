<?php

// return [
//     'paths' => ['api/*', 'login', 'logout'], // Define routes that should allow CORS
//     'allowed_methods' => ['*'],
//     'allowed_origins' => ['*'],  // You can specify a domain instead of '*'
//     'allowed_origins_patterns' => [],
//     'allowed_headers' => ['*'],
//     'exposed_headers' => [],
//     'max_age' => 0,
//     'supports_credentials' => true,
//     //'allowed_headers' => ['Authorization', 'Content-Type', 'X-Requested-With'],

// ];

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        'https://smileslogistics-frontend.vercel.app',
        'http://localhost:3000' // for local development
    ],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];