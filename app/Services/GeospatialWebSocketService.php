<?php

namespace App\Services;

use App\Models\Location;
use App\Models\WeatherData;
use App\Events\WeatherDataUpdated;
use Illuminate\Support\Facades\Log;

class GeospatialWebSocketService
{
    private OpenWeatherService $weatherService;
    private array $activeConnections = [];

    public function __construct(OpenWeatherService $weatherService)
    {
        $this->weatherService = $weatherService;
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
