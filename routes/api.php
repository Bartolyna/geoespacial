<?php

use App\Http\Controllers\GeospatialController;
use App\Http\Controllers\PostGISController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::prefix('geospatial')->group(function () {
    // Rutas de solo lectura (más permisivas)
    Route::get('/locations', [GeospatialController::class, 'getLocations']);
    Route::get('/locations/{location}', [GeospatialController::class, 'getLocationWeather']);
    Route::get('/summary', [GeospatialController::class, 'getSummary']);
    Route::get('/stats', [GeospatialController::class, 'getServiceStats']);
    
    // Rutas de escritura (más restrictivas)
    Route::middleware(['throttle:10,1'])->group(function () { // 10 requests por minuto
        Route::post('/locations', [GeospatialController::class, 'createLocation']);
        Route::put('/locations/{location}/update', [GeospatialController::class, 'updateLocationWeather']);
        Route::patch('/locations/{location}/toggle', [GeospatialController::class, 'toggleLocation']);
        Route::delete('/locations/{location}', [GeospatialController::class, 'deleteLocation']);
    });
});

// Rutas PostGIS para operaciones geoespaciales avanzadas
Route::prefix('postgis')->group(function () {
    // Rutas de solo lectura (información y consultas)
    Route::get('/info', [PostGISController::class, 'getInfo']);
    Route::get('/stats', [PostGISController::class, 'getGeographicStats']);
    Route::get('/centroid', [PostGISController::class, 'getCentroid']);
    
    // Búsquedas geoespaciales
    Route::post('/search/radius', [PostGISController::class, 'findWithinRadius']);
    Route::post('/search/nearest', [PostGISController::class, 'getNearestLocations']);
    Route::post('/search/polygon', [PostGISController::class, 'findInPolygon']);
    Route::post('/distance', [PostGISController::class, 'calculateDistance']);
    
    // Operaciones más intensivas (con rate limiting estricto)
    Route::middleware(['throttle:5,1'])->group(function () { // 5 requests por minuto
        Route::post('/locations/{location}/buffer', [PostGISController::class, 'createBuffer']);
    });
});


Route::get('/websocket/stats', function (Request $request) {
    // Loggear acceso a estadísticas sensibles
    Log::info('Acceso a estadísticas WebSocket', [
        'ip' => $request->ip(),
        'user_agent' => $request->userAgent(),
        'timestamp' => now()->toISOString(),
    ]);
    
    try {
        // Verificar estado del servidor Reverb
        $reverbHost = config('broadcasting.connections.reverb.options.host', 'reverb');
        $reverbPort = config('broadcasting.connections.reverb.options.port', 8080);
        
        $isOnline = false;
        $connection = @fsockopen($reverbHost, $reverbPort, $errno, $errstr, 2);
        if ($connection) {
            $isOnline = true;
            fclose($connection);
        }
        
        // Contar usuarios reales activos en la URL específica
        $activeUsers = 0;
        $websocketConnections = 0;
        $uniqueIPs = [];
        
        try {
            // Verificar la tabla cache directamente
            $cacheRecords = \Illuminate\Support\Facades\DB::table('cache')
                ->where('key', 'like', '%active_user_%')
                ->where('expiration', '>', time())
                ->get();
            
            foreach ($cacheRecords as $record) {
                try {
                    // Laravel cache almacena datos serializados
                    $rawValue = $record->value;
                    
                    // Intentar diferentes formas de deserializar
                    if (str_starts_with($rawValue, 's:') || str_starts_with($rawValue, 'a:') || str_starts_with($rawValue, 'O:')) {
                        $data = unserialize($rawValue);
                    } else {
                        $decoded = base64_decode($rawValue);
                        if ($decoded !== false) {
                            $data = unserialize($decoded);
                        } else {
                            $data = json_decode($rawValue, true);
                        }
                    }
                    
                    if ($data && is_array($data) && isset($data['last_seen'])) {
                        $timeDiff = time() - $data['last_seen'];
                        
                        // Solo contar si estuvo activo en los últimos 25 segundos
                        if ($timeDiff < 25) {
                            $activeUsers++;
                            
                            // Contar conexiones WebSocket activas
                            if (isset($data['websocket_connected']) && $data['websocket_connected']) {
                                $websocketConnections++;
                            }
                            
                            // Registrar IP única
                            if (isset($data['ip_address']) && !empty($data['ip_address'])) {
                                $uniqueIPs[$data['ip_address']] = true;
                            }
                        }
                    }
                } catch (\Exception $deserError) {
                    // Silenciosamente ignorar errores de deserialización
                    continue;
                }
            }
            
        } catch (\Exception $e) {
            $activeUsers = $isOnline ? 1 : 0;
            $websocketConnections = $activeUsers;
        }
        
        return response()->json([
            'websocket_server' => 'reverb',
            'status' => $isOnline ? 'online' : 'offline',
            'active_connections' => $websocketConnections,
            'active_users' => $activeUsers,
            'unique_ips' => count($uniqueIPs),
            'active_channels' => $isOnline ? 1 : 0,
            'server_host' => $reverbHost,
            'server_port' => $reverbPort,
            'url_monitored' => url('/geospatial-dashboard'),
            'real_time_tracking' => true,
            'timestamp' => now()->toISOString()
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'websocket_server' => 'reverb',
            'status' => 'error',
            'active_connections' => 0,
            'active_users' => 0,
            'unique_ips' => 0,
            'active_channels' => 0,
            'error' => $e->getMessage(),
            'timestamp' => now()->toISOString()
        ]);
    }
});

Route::post('/websocket/heartbeat', function (Request $request) {
    // Loggear heartbeat con validación
    $validated = $request->validate([
        'url' => 'required|url|max:500',
    ]);
    
    Log::debug('Heartbeat WebSocket recibido', [
        'ip' => $request->ip(),
        'url' => $validated['url'],
        'timestamp' => now()->toISOString(),
    ]);
    try {
        $connectionId = $request->input('connection_id', 'unknown');
        $tabId = $request->input('tab_id', 'unknown');
        $sessionId = $request->input('session_id', 'unknown');
        $url = $request->input('url', '');
        $userAgent = $request->input('user_agent', '');
        $websocketConnected = $request->input('websocket_connected', false);
        $timestamp = $request->input('timestamp', time() * 1000);
        
        // Información detallada de la conexión
        $connectionData = [
            'connection_id' => $connectionId,
            'tab_id' => $tabId,
            'session_id' => $sessionId,
            'url' => $url,
            'user_agent' => $userAgent,
            'websocket_connected' => $websocketConnected,
            'ip_address' => $request->ip(),
            'last_seen' => time(),
            'client_timestamp' => $timestamp
        ];
        
        // Usar un cache key más específico
        $cacheKey = 'active_user_' . $connectionId;
        
        // Registrar la conexión como activa por 30 segundos
        \Illuminate\Support\Facades\Cache::put($cacheKey, $connectionData, 30);
        
        return response()->json([
            'status' => 'registered',
            'connection_id' => $connectionId,
            'message' => 'Usuario activo registrado',
            'timestamp' => now()->toISOString()
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'error' => $e->getMessage()
        ], 500);
    }
});

Route::get('/websocket/info', function () {
    return response()->json([
        'reverb_host' => 'localhost',
        'reverb_port' => 8081,
        'reverb_scheme' => 'http',
        'app_key' => env('REVERB_APP_KEY', 'local-app-key'),
        'channels' => [
            'main' => config('geospatial.channels.main', 'geospatial'),
            'alerts' => config('geospatial.channels.alerts', 'geospatial.alerts'),
            'summary' => config('geospatial.channels.summary', 'geospatial.summary'),
        ]
    ]);
});
