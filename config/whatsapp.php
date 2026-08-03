<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default WhatsApp Provider
    |--------------------------------------------------------------------------
    |
    | Driver used when the company connection does not specify a provider.
    | Available: wppconnect
    |
    */
    'default' => env('WHATSAPP_PROVIDER', 'wppconnect'),

    /*
    |--------------------------------------------------------------------------
    | Provider drivers
    |--------------------------------------------------------------------------
    */
    'providers' => [
        'wppconnect' => [
            'base_url' => env('WPPCONNECT_BASE_URL', 'http://localhost:21465'),
            'session' => env('WPPCONNECT_SESSION', 'geosales'),
            'timeout' => (int) env('WPPCONNECT_TIMEOUT', 15),
            'endpoints' => [
                'send_message' => '/api/{session}/send-message',
                'send_file' => '/api/{session}/send-file',
                'status' => '/api/{session}/status-session',
            ],
        ],
    ],
];
