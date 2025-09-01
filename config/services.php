<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // OpenWeather API Configuration

    'openweather' => [
        'api_key' => env('WEATHER_API_KEY'),
        'base_url' => env('WEATHER_API_BASE_URL', 'https://api.openweathermap.org/data/2.5'),
        'timeout' => env('WEATHER_API_TIMEOUT', 10),
        'units' => env('WEATHER_API_UNITS', 'metric'),
        'lang' => env('WEATHER_API_LANG', 'es'),
        'verify_ssl' => env('WEATHER_API_VERIFY_SSL', true),
    ],

    // LLM Services Configuration

    'openai' => [
        'api_key' => env('OPENAI_API_KEY', 'your-openai-api-key-here'),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        'model' => env('OPENAI_MODEL', 'gpt-3.5-turbo'),
        'max_tokens' => env('OPENAI_MAX_TOKENS', 2048),
        'temperature' => env('OPENAI_TEMPERATURE', 0.7),
        'timeout' => env('OPENAI_TIMEOUT', 30),
        'verify_ssl' => env('OPENAI_VERIFY_SSL', true),
    ],

    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY', 'your-anthropic-api-key-here'),
        'base_url' => env('ANTHROPIC_BASE_URL', 'https://api.anthropic.com/v1'),
        'model' => env('ANTHROPIC_MODEL', 'claude-3-haiku-20240307'),
        'max_tokens' => env('ANTHROPIC_MAX_TOKENS', 2048),
        'timeout' => env('ANTHROPIC_TIMEOUT', 30),
        'verify_ssl' => env('ANTHROPIC_VERIFY_SSL', true),
    ],

    'llm' => [
        'default_provider' => env('LLM_DEFAULT_PROVIDER', 'simulation'),
        'cache_enabled' => env('LLM_CACHE_ENABLED', true),
        'cache_duration' => env('LLM_CACHE_DURATION', 24), // hours
        'rate_limit' => env('LLM_RATE_LIMIT', 30), // requests per minute
        'simulation_enabled' => env('LLM_SIMULATION_ENABLED', true),
    ],

];
