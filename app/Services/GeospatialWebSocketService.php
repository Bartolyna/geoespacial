<?php

namespace App\Services;

use App\Models\Location;
use App\Models\WeatherData;
use App\Events\WeatherDataUpdated;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class GeospatialWebSocketService
{
    private OpenWeatherService $weatherService;
    private array $activeConnections = [];
    private array $connectionsByIp = [];
    private int $maxConnections;
    private int $maxConnectionsPerIp;
    private int $connectionTimeout;
    private int $heartbeatInterval;

    public function __construct(OpenWeatherService $weatherService)
    {
        $this->weatherService = $weatherService;
        $this->maxConnections = config('geospatial.limits.max_connections', 100);
        $this->maxConnectionsPerIp = config('geospatial.limits.max_connections_per_ip', 5);
        $this->connectionTimeout = config('geospatial.websocket.connection_timeout', 300);
        $this->heartbeatInterval = config('geospatial.websocket.heartbeat_interval', 30);
    }

    // Gestión de conexiones concurrentes
    public function registerConnection(string $connectionId, string $ipAddress, array $metadata = []): bool
    {
        // Verificar límite global de conexiones
        if (count($this->activeConnections) >= $this->maxConnections) {
            Log::warning('Límite global de conexiones alcanzado', [
                'connection_id' => $connectionId,
                'current_connections' => count($this->activeConnections),
                'max_connections' => $this->maxConnections
            ]);
            return false;
        }

        // Verificar límite por IP
        $ipConnections = $this->connectionsByIp[$ipAddress] ?? [];
        if (count($ipConnections) >= $this->maxConnectionsPerIp) {
            Log::warning('Límite de conexiones por IP alcanzado', [
                'connection_id' => $connectionId,
                'ip_address' => $ipAddress,
                'current_ip_connections' => count($ipConnections),
                'max_per_ip' => $this->maxConnectionsPerIp
            ]);
            return false;
        }

        // Registrar conexión
        $connection = [
            'id' => $connectionId,
            'ip_address' => $ipAddress,
            'connected_at' => Carbon::now(),
            'last_heartbeat' => Carbon::now(),
            'metadata' => $metadata,
            'reconnect_attempts' => 0,
            'status' => 'active'
        ];

        $this->activeConnections[$connectionId] = $connection;
        $this->connectionsByIp[$ipAddress][] = $connectionId;

        // Almacenar en caché para persistencia
        Cache::put("websocket_connection_{$connectionId}", $connection, $this->connectionTimeout);

        Log::info('Nueva conexión WebSocket registrada', [
            'connection_id' => $connectionId,
            'ip_address' => $ipAddress,
            'total_connections' => count($this->activeConnections),
            'ip_connections' => count($this->connectionsByIp[$ipAddress])
        ]);

        return true;
    }

    public function unregisterConnection(string $connectionId): void
    {
        if (!isset($this->activeConnections[$connectionId])) {
            return;
        }

        $connection = $this->activeConnections[$connectionId];
        $ipAddress = $connection['ip_address'];

        // Remover de conexiones activas
        unset($this->activeConnections[$connectionId]);

        // Remover de conexiones por IP
        if (isset($this->connectionsByIp[$ipAddress])) {
            $this->connectionsByIp[$ipAddress] = array_filter(
                $this->connectionsByIp[$ipAddress],
                fn($id) => $id !== $connectionId
            );

            if (empty($this->connectionsByIp[$ipAddress])) {
                unset($this->connectionsByIp[$ipAddress]);
            }
        }

        // Remover de caché
        Cache::forget("websocket_connection_{$connectionId}");

        Log::info('Conexión WebSocket desregistrada', [
            'connection_id' => $connectionId,
            'ip_address' => $ipAddress,
            'connection_duration' => Carbon::now()->diffInSeconds($connection['connected_at']),
            'remaining_connections' => count($this->activeConnections)
        ]);
    }

    // Heartbeat para mantener conexiones vivas
    public function updateHeartbeat(string $connectionId): bool
    {
        if (!isset($this->activeConnections[$connectionId])) {
            return false;
        }

        $this->activeConnections[$connectionId]['last_heartbeat'] = Carbon::now();
        
        // Actualizar en caché
        Cache::put(
            "websocket_connection_{$connectionId}",
            $this->activeConnections[$connectionId],
            $this->connectionTimeout
        );

        return true;
    }

    // Limpiar conexiones inactivas
    public function cleanupStaleConnections(): int
    {
        $cleaned = 0;
        $timeout = Carbon::now()->subSeconds($this->connectionTimeout);

        foreach ($this->activeConnections as $connectionId => $connection) {
            if (Carbon::parse($connection['last_heartbeat'])->lt($timeout)) {
                $this->unregisterConnection($connectionId);
                $cleaned++;
            }
        }

        if ($cleaned > 0) {
            Log::info('Conexiones inactivas limpiadas', [
                'cleaned_connections' => $cleaned,
                'remaining_connections' => count($this->activeConnections)
            ]);
        }

        return $cleaned;
    }

    // Obtener estadísticas de conexiones
    public function getConnectionStats(): array
    {
        $connectionsByIpStats = [];
        foreach ($this->connectionsByIp as $ip => $connections) {
            $connectionsByIpStats[$ip] = count($connections);
        }

        return [
            'total_connections' => count($this->activeConnections),
            'max_connections' => $this->maxConnections,
            'connections_by_ip' => $connectionsByIpStats,
            'oldest_connection' => $this->getOldestConnection(),
            'newest_connection' => $this->getNewestConnection(),
            'average_connection_age' => $this->getAverageConnectionAge()
        ];
    }

    private function getOldestConnection(): ?array
    {
        if (empty($this->activeConnections)) {
            return null;
        }

        $oldest = array_reduce($this->activeConnections, function ($carry, $connection) {
            if (!$carry || Carbon::parse($connection['connected_at'])->lt(Carbon::parse($carry['connected_at']))) {
                return $connection;
            }
            return $carry;
        });

        return [
            'id' => $oldest['id'],
            'connected_at' => $oldest['connected_at'],
            'duration_seconds' => Carbon::now()->diffInSeconds($oldest['connected_at'])
        ];
    }

    private function getNewestConnection(): ?array
    {
        if (empty($this->activeConnections)) {
            return null;
        }

        $newest = array_reduce($this->activeConnections, function ($carry, $connection) {
            if (!$carry || Carbon::parse($connection['connected_at'])->gt(Carbon::parse($carry['connected_at']))) {
                return $connection;
            }
            return $carry;
        });

        return [
            'id' => $newest['id'],
            'connected_at' => $newest['connected_at'],
            'duration_seconds' => Carbon::now()->diffInSeconds($newest['connected_at'])
        ];
    }

    private function getAverageConnectionAge(): int
    {
        if (empty($this->activeConnections)) {
            return 0;
        }

        $totalAge = array_sum(array_map(function ($connection) {
            return Carbon::now()->diffInSeconds($connection['connected_at']);
        }, $this->activeConnections));

        return intval($totalAge / count($this->activeConnections));
    }

    // Tiempo real
    public function startRealTimeMonitoring(): void
    {
        $interval = config('geospatial.update_interval', env('GEOSPATIAL_UPDATE_INTERVAL', 60));
        
        Log::info('Iniciando monitoreo geoespacial en tiempo real', [
            'interval' => $interval . ' segundos'
        ]);

        while (true) {
            try {
                $this->updateAllLocations();
                sleep($interval);
            } catch (\Exception $e) {
                Log::error('Error en el monitoreo en tiempo real', [
                    'error' => $e->getMessage()
                ]);
                
                // Esperar antes de reintentar
                sleep(30);
            }
        }
    }

    // Actualizar todas las ubicaciones

    public function updateAllLocations(): void
    {
        $locations = Location::active()->get();
        
        foreach ($locations as $location) {
            $this->updateLocationWeather($location);
        }
    }

    // Actualizar datos meteorológicos 

    public function updateLocationWeather(Location $location): void
    {
        try {
            $weatherData = $this->weatherService->fetchAndStoreWeatherData($location);
            
            if ($weatherData) {
                // Emitir evento WebSocket
                WeatherDataUpdated::dispatch($location, $weatherData);
                
                Log::info('Datos meteorológicos actualizados', [
                    'location_id' => $location->id,
                    'location_name' => $location->name,
                    'temperature' => $weatherData->temperature,
                    'weather' => $weatherData->weather_main
                ]);
            } else {
                Log::warning('No se pudieron obtener datos meteorológicos', [
                    'location_id' => $location->id,
                    'location_name' => $location->name
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error al actualizar ubicación', [
                'location_id' => $location->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    // Obtener estadísticas del servicio
    public function getServiceStats(): array
    {
        $activeLocations = Location::active()->count();
        $totalWeatherRecords = WeatherData::count();
        $recentRecords = WeatherData::recent(1)->count();
        
        return [
            'active_locations' => $activeLocations,
            'total_weather_records' => $totalWeatherRecords,
            'recent_records_1h' => $recentRecords,
            'service_status' => 'running',
            'last_update' => now(),
        ];
    }


    // Enviar datos actuales a WebSocket para una ubicación
    public function broadcastCurrentData(Location $location): void
    {
        $weatherData = $location->latestWeatherData();
        
        if ($weatherData) {
            WeatherDataUpdated::dispatch($location, $weatherData);
        }
    }

    // Resumen de ubicaciones
    public function broadcastSummary(): void
    {
        $locations = Location::active()->with('weatherData')->get();
        $summary = [];
        
        foreach ($locations as $location) {
            $latestData = $location->latestWeatherData();
            
            if ($latestData) {
                $summary[] = [
                    'location' => [
                        'id' => $location->id,
                        'name' => $location->name,
                        'city' => $location->city,
                        'country' => $location->country,
                        'coordinates' => $location->coordinates,
                    ],
                    'weather' => [
                        'temperature' => $latestData->temperature,
                        'feels_like' => $latestData->feels_like,
                        'humidity' => $latestData->humidity,
                        'pressure' => $latestData->pressure,
                        'weather_main' => $latestData->weather_main,
                        'weather_description' => $latestData->weather_description,
                        'weather_icon' => $latestData->weather_icon,
                        'wind_speed' => $latestData->wind_speed,
                        'wind_direction' => $latestData->wind_direction,
                        'last_update' => $latestData->dt->toISOString(),
                    ]
                ];
            }
        }

        // Emitir resumen general
        broadcast(new \App\Events\WeatherSummaryUpdated($summary));
    }
}
