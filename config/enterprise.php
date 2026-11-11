<?php

return [
    'automation_secret' => env('AUTOMATION_WEBHOOK_SECRET'),
    'n8n_webhook_url' => env('N8N_WEBHOOK_URL'),
    'ai' => [
        'enabled' => env('AI_ENABLED', false),
        'provider' => env('AI_PROVIDER', 'ollama'),
        'base_url' => env('OLLAMA_BASE_URL', 'http://ollama:11434'),
        'model' => env('OLLAMA_MODEL', 'qwen2.5:3b'),
        'timeout' => (int) env('AI_TIMEOUT', 45),
    ],
    'whatsapp' => [
        'enabled' => env('WHATSAPP_ENABLED', false),
        'base_url' => env('WHATSAPP_API_BASE', 'https://graph.facebook.com'),
        'version' => env('WHATSAPP_API_VERSION', 'v21.0'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'token' => env('WHATSAPP_ACCESS_TOKEN'),
    ],
];
