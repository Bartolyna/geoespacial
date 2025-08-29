<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuración del Sistema Geoespacial
    |--------------------------------------------------------------------------
    */

    // Intervalo de actualización en segundos
    'update_interval' => env('GEOSPATIAL_UPDATE_INTERVAL', 60),

    // Configuración de conexiones WebSocket
    'websocket' => [
        'max_reconnect_attempts' => env('GEOSPATIAL_MAX_RECONNECT_ATTEMPTS', 5),
        'reconnect_delay' => env('GEOSPATIAL_RECONNECT_DELAY', 5),
        'connection_timeout' => env('GEOSPATIAL_CONNECTION_TIMEOUT', 300),
        'heartbeat_interval' => env('GEOSPATIAL_HEARTBEAT_INTERVAL', 30),
        'exponential_backoff' => env('GEOSPATIAL_EXPONENTIAL_BACKOFF', true),
        'max_backoff_delay' => env('GEOSPATIAL_MAX_BACKOFF_DELAY', 60),
        'ping_interval' => env('GEOSPATIAL_PING_INTERVAL', 25),
        'pong_timeout' => env('GEOSPATIAL_PONG_TIMEOUT', 10),
    ],

    // Límites del sistema
    'limits' => [
        'max_locations' => env('GEOSPATIAL_MAX_LOCATIONS', 50),
        'max_connections' => env('GEOSPATIAL_MAX_CONNECTIONS', 100),
        'max_connections_per_ip' => env('GEOSPATIAL_MAX_CONNECTIONS_PER_IP', 5),
        'cache_ttl' => env('GEOSPATIAL_CACHE_TTL', 300), // 5 minutos
        'data_retention_days' => env('GEOSPATIAL_DATA_RETENTION_DAYS', 30),
    ],

    // Configuración de alertas
    'alerts' => [
        'enabled' => env('GEOSPATIAL_ALERTS_ENABLED', true),
        'temperature_threshold' => env('GEOSPATIAL_TEMP_THRESHOLD', 35),
        'wind_speed_threshold' => env('GEOSPATIAL_WIND_THRESHOLD', 50),
        'precipitation_threshold' => env('GEOSPATIAL_PRECIPITATION_THRESHOLD', 10),
    ],

    // Canales de broadcasting
    'channels' => [
        'main' => 'geospatial',
        'location_prefix' => 'location.',
        'alerts' => 'geospatial.alerts',
        'summary' => 'geospatial.summary',
    ],

    // Configuración de logging
    'logging' => [
        'level' => env('GEOSPATIAL_LOG_LEVEL', 'info'),
        'enable_api_logging' => env('GEOSPATIAL_LOG_API', true),
        'enable_websocket_logging' => env('GEOSPATIAL_LOG_WEBSOCKET', false),
    ],
];
