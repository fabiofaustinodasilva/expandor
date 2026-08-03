<?php

return [
    'login' => [
        'max_attempts' => (int) env('SECURITY_LOGIN_MAX_ATTEMPTS', 5),
        'decay_seconds' => (int) env('SECURITY_LOGIN_DECAY_SECONDS', 60),
    ],

    'api' => [
        'max_attempts' => (int) env('SECURITY_API_MAX_ATTEMPTS', 60),
        'decay_seconds' => (int) env('SECURITY_API_DECAY_SECONDS', 60),
    ],

    'export' => [
        'disk' => env('SECURITY_EXPORT_DISK', 'local'),
        'path' => 'exports',
        'expires_hours' => (int) env('SECURITY_EXPORT_EXPIRES_HOURS', 24),
    ],
];
