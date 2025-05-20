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

    'paths' => ['api/*', 'login', 'logout'],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['http://localhost:3000'], // ✅ Use exact origin

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
