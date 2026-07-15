<?php

return [
    // Enable or disable API logging globally
    'enabled' => env('API_LOG_ENABLED', true),

    // HTTP methods that should be excluded from logging
    'exclude_methods' => [
        'GET',
        'HEAD',
        'OPTIONS',
    ],

    // Route paths (relative to app) that should be excluded from logging
    'exclude_routes' => [
        'api/auth/login',
        'api/auth/backend-login',
        'api/auth/verify',
        'api/auth/me',
        'api/auth/send-otp',
    ],
];
