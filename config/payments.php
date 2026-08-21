<?php

return [
    'default' => env('PAYMENT_PROVIDER', 'mercadopago'),

    /*
    | null = decide por APP_ENV (fake só em testing)
    | true/false = força permissão do provider fake
    */
    'allow_fake' => env('PAYMENT_ALLOW_FAKE'),

    'currency' => env('PAYMENT_CURRENCY', 'BRL'),

    'checkout' => [
        'success_url' => env('PAYMENT_CHECKOUT_SUCCESS_URL', '/checkout/success'),
        'cancel_url' => env('PAYMENT_CHECKOUT_CANCEL_URL', '/checkout/cancel'),
        'session_ttl_minutes' => (int) env('PAYMENT_CHECKOUT_TTL_MINUTES', 60),
    ],

    'trial_days' => (int) env('PAYMENT_TRIAL_DAYS', 0),

    /*
    | Fidelidade mínima para NOVAS assinaturas comerciais (cobrança continua mensal).
    */
    'fidelity' => [
        'minimum_term_months' => (int) env('BILLING_MINIMUM_TERM_MONTHS', 6),
    ],

    /*
    | Tolerância após vencimento antes da suspensão financeira automática.
    */
    'delinquency' => [
        'grace_days' => (int) env('BILLING_GRACE_DAYS', 5),
    ],

    'providers' => [
        'asaas' => [
            'base_url' => env('ASAAS_BASE_URL', 'https://sandbox.asaas.com/api/v3'),
            'api_key' => env('ASAAS_API_KEY'),
            'webhook_token' => env('ASAAS_WEBHOOK_TOKEN'),
            'timeout' => (int) env('ASAAS_TIMEOUT', 30),
        ],
        'mercadopago' => [
            'base_url' => env('MERCADO_PAGO_BASE_URL', 'https://api.mercadopago.com'),
            'access_token' => env('MERCADO_PAGO_TOKEN'),
            'webhook_token' => env('MERCADO_PAGO_WEBHOOK_TOKEN'),
            'timeout' => (int) env('MERCADO_PAGO_TIMEOUT', 30),
        ],
        'stripe' => [
            'key' => env('STRIPE_KEY'),
            'secret' => env('STRIPE_SECRET'),
            'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        ],
        'fake' => [
            'webhook_token' => env('FAKE_PAYMENT_WEBHOOK_TOKEN', 'fake-webhook-token'),
        ],
    ],
];
