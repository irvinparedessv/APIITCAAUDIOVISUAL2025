<?php

return [

    'paths' => ['api/*', 'login', 'logout', 'sanctum/csrf-cookie', 'broadcasting/auth', 'storage/*',],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['http://localhost:5173', 'http://127.0.0.1:5173', 'https://prestamod612.online', '*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
