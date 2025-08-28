<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\WeatherData;
use App\Services\OpenWeatherService;
use App\Services\GeospatialWebSocketService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class GeospatialController extends Controller
{
    private OpenWeatherService $weatherService;
    private GeospatialWebSocketService $webSocketService;

    public function __construct(
        OpenWeatherService $weatherService,
        GeospatialWebSocketService $webSocketService
    ) {
        $this->weatherService = $weatherService;
        $this->webSocketService = $webSocketService;
    }

    // Obtener todas las ubicaciones activas
    public function getLocations(): JsonResponse
    {
        $locations = Location::active()
            ->with(['weatherData' => function ($query) {
                $query->latest('dt')->limit(1);
            }])
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $locations,
            'total' => $locations->count(),
        ]);
    }

    // Crear una nueva ubicación
    public function createLocation(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'city' => 'required|string|max:255',
                'country' => 'required|string|max:255',
                'state' => 'nullable|string|max:255',
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180',
                'active' => 'boolean',
                'metadata' => 'nullable|array',
            ]);

            $location = Location::create($validated);

            // Obtener datos meteorológicos iniciales
            $weatherData = $this->weatherService->fetchAndStoreWeatherData($location);

            return response()->json([
                'status' => 'success',
                'message' => 'Ubicación creada exitosamente',
                'data' => [
                    'location' => $location,
                    'weather_data' => $weatherData,
                ],
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al crear la ubicación',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Obtener datos meteorológicos 
    public function getLocationWeather(Location $location): JsonResponse
    {
        $weatherData = $location->weatherData()
            ->orderBy('dt', 'desc')
            ->limit(24)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'location' => $location,
                'weather_data' => $weatherData,
                'latest' => $weatherData->first(),
            ],
        ]);
    }

    // Actualizar datos meteorológicos 
    public function updateLocationWeather(Location $location): JsonResponse
    {
        try {
            $weatherData = $this->weatherService->fetchAndStoreWeatherData($location);

            if ($weatherData) {
                // Emitir evento WebSocket
                $this->webSocketService->broadcastCurrentData($location);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Datos actualizados exitosamente',
                    'data' => $weatherData,
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'No se pudieron obtener datos meteorológicos',
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al actualizar datos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Obtener resumen de todas las ubicaciones
    public function getSummary(): JsonResponse
    {
        $locations = Location::active()->with('weatherData')->get();
        $summary = [];
        $totalLocations = 0;
        $activeAlerts = 0;

        foreach ($locations as $location) {
            $latestData = $location->latestWeatherData();
            
            if ($latestData) {
                $locationSummary = [
                    'location' => [
                        'id' => $location->id,
                        'name' => $location->name,
                        'city' => $location->city,
                        'country' => $location->country,
                        'coordinates' => $location->coordinates,
                    ],
                    'weather' => [
                        'temperature' => $latestData->temperature,
                        'weather_main' => $latestData->weather_main,
                        'weather_description' => $latestData->weather_description,
                        'humidity' => $latestData->humidity,
                        'wind_speed' => $latestData->wind_speed,
                        'last_update' => $latestData->dt->toISOString(),
                    ],
                ];

                // Verificar alertas
                $hasAlert = $this->checkWeatherAlerts($latestData);
                if ($hasAlert) {
                    $activeAlerts++;
                    $locationSummary['alert'] = $hasAlert;
                }

                $summary[] = $locationSummary;
                $totalLocations++;
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => $summary,
            'statistics' => [
                'total_locations' => $totalLocations,
                'active_alerts' => $activeAlerts,
                'last_update' => now()->toISOString(),
            ],
        ]);
    }

    // Obtener estadísticas del servicio
    public function getServiceStats(): JsonResponse
    {
        $stats = $this->webSocketService->getServiceStats();
        
        return response()->json([
            'status' => 'success',
            'data' => $stats,
        ]);
    }

    // Activar/desactivar una ubicación - No se utiliza por el momento
    public function toggleLocation(Location $location): JsonResponse
    {
        $location->update(['active' => !$location->active]);

        return response()->json([
            'status' => 'success',
            'message' => $location->active ? 'Ubicación activada' : 'Ubicación desactivada',
            'data' => $location,
        ]);
    }

    // Eliminar una ubicación - No se utiliza por el momento
    public function deleteLocation(Location $location): JsonResponse
    {
        try {
            $location->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Ubicación eliminada exitosamente',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al eliminar la ubicación',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Verificar alertas meteorológicas
    private function checkWeatherAlerts(WeatherData $weatherData): array|null
    {
        $alerts = [];
        
        // Verificar temperatura alta
        if ($weatherData->temperature > config('geospatial.alerts.temperature_threshold', 35)) {
            $alerts[] = [
                'type' => 'high_temperature',
                'message' => "Temperatura alta: {$weatherData->temperature}°C",
                'severity' => 'warning',
            ];
        }

        // Verificar viento fuerte
        if ($weatherData->wind_speed > config('geospatial.alerts.wind_speed_threshold', 50)) {
            $alerts[] = [
                'type' => 'strong_wind',
                'message' => "Viento fuerte: {$weatherData->wind_speed} km/h",
                'severity' => 'warning',
            ];
        }

        // Verificar precipitación intensa
        if ($weatherData->rain_1h > config('geospatial.alerts.precipitation_threshold', 10)) {
            $alerts[] = [
                'type' => 'heavy_rain',
                'message' => "Lluvia intensa: {$weatherData->rain_1h} mm/h",
                'severity' => 'alert',
            ];
        }

        return !empty($alerts) ? $alerts : null;
    }
}
