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

];
