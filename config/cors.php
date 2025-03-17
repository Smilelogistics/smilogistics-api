<?php

return [
    'paths' => ['api/*', 'login', 'logout'], // Define routes that should allow CORS
    'allowed_methods' => ['*'],
    'allowed_origins' => ['*'],  // You can specify a domain instead of '*'
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
