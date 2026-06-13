<?php

return [
    'api_key' => env('ANTHROPIC_API_KEY', ''),
    'auth_token' => env('ANTHROPIC_AUTH_TOKEN', ''),
    'base_url' => env('ANTHROPIC_BASE_URL', ''),
    'model' => env('ANTHROPIC_MODEL', 'claude-sonnet-4-6'),
];
