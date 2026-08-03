<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default AI Provider
    |--------------------------------------------------------------------------
    |
    | Driver used by ProviderFactory. Available: openai
    |
    */
    'default' => env('AI_PROVIDER', 'openai'),

    /*
    |--------------------------------------------------------------------------
    | Assistive-only guardrails
    |--------------------------------------------------------------------------
    |
    | The AI layer must remain suggestive. It never writes CRM data or
    | executes commercial actions automatically.
    |
    */
    'system_preamble' => <<<'TXT'
Você é o assistente Expandor AI. Suas respostas são apenas sugestões.
Nunca altere dados do CRM, nunca execute ações comerciais e nunca envie
mensagens WhatsApp. Oriente o usuário a confirmar qualquer decisão humana.
Baseie-se apenas no contexto fornecido; não invente números ou fatos.
TXT,

    /*
    |--------------------------------------------------------------------------
    | Provider drivers
    |--------------------------------------------------------------------------
    */
    'providers' => [
        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
            'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
            'timeout' => (int) env('OPENAI_TIMEOUT', 30),
            'chat_endpoint' => '/chat/completions',
        ],
    ],
];
