<?php

namespace App\Services;

use App\Models\Location;
use App\Models\WeatherData;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class OpenWeatherService
{
    private string $apiKey;
    private string $baseUrl;
    private int $timeout;
    private string $units;
    private string $lang;

    public function __construct()
    {
        $this->apiKey = config('services.openweather.api_key', env('WEATHER_API_KEY'));
        $this->baseUrl = config('services.openweather.base_url', env('WEATHER_API_BASE_URL', 'https://api.openweathermap.org/data/2.5'));
        $this->timeout = config('services.openweather.timeout', env('WEATHER_API_TIMEOUT', 10));
        $this->units = config('services.openweather.units', env('WEATHER_API_UNITS', 'metric'));
        $this->lang = config('services.openweather.lang', env('WEATHER_API_LANG', 'es'));
    }

    // Datos meteorológicos 
    public function getCurrentWeather(Location $location): ?array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get("{$this->baseUrl}/weather", [
                    'lat' => $location->latitude,
                    'lon' => $location->longitude,
                    'appid' => $this->apiKey,
                    'units' => $this->units,
                    'lang' => $this->lang,
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Error al obtener datos meteorológicos', [
                'location_id' => $location->id,
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('Excepción al obtener datos meteorológicos', [
                'location_id' => $location->id,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    //Pronóstico ubicacion
    public function getForecast(Location $location, int $days = 5): ?array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get("{$this->baseUrl}/forecast", [
                    'lat' => $location->latitude,
                    'lon' => $location->longitude,
                    'appid' => $this->apiKey,
                    'units' => $this->units,
                    'lang' => $this->lang,
                    'cnt' => $days * 8 // 8 registros por día (cada 3 horas)
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Error al obtener pronóstico', [
                'location_id' => $location->id,
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('Excepción al obtener pronóstico', [
                'location_id' => $location->id,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    // Almacenar datos meteorológicos en la base de datos
    public function storeWeatherData(Location $location, array $weatherData): ?WeatherData
    {
        try {
            // Extraer datos principales
            $main = $weatherData['main'] ?? [];
            $weather = $weatherData['weather'][0] ?? [];
            $wind = $weatherData['wind'] ?? [];
            $clouds = $weatherData['clouds'] ?? [];
            $rain = $weatherData['rain'] ?? [];
            $snow = $weatherData['snow'] ?? [];
            $sys = $weatherData['sys'] ?? [];

            // Obtener temperatura actual como fallback para min/max
            $currentTemp = $main['temp'] ?? 0;

            $data = [
                'location_id' => $location->id,
                'temperature' => $currentTemp,
                'feels_like' => $main['feels_like'] ?? $currentTemp,
                'temp_min' => $main['temp_min'] ?? $currentTemp,
                'temp_max' => $main['temp_max'] ?? $currentTemp,
                'pressure' => $main['pressure'] ?? 0,
                'humidity' => $main['humidity'] ?? 0,
                'visibility' => ($weatherData['visibility'] ?? 0) / 1000, // Convertir a km
                'wind_speed' => $wind['speed'] ?? null,
                'wind_deg' => $wind['deg'] ?? null,
                'wind_gust' => $wind['gust'] ?? null,
                'rain_1h' => $rain['1h'] ?? null,
                'rain_3h' => $rain['3h'] ?? null,
                'snow_1h' => $snow['1h'] ?? null,
                'snow_3h' => $snow['3h'] ?? null,
                'clouds' => $clouds['all'] ?? null,
                'weather_main' => $weather['main'] ?? 'Unknown',
                'weather_description' => $weather['description'] ?? 'Sin descripción',
                'weather_icon' => $weather['icon'] ?? 'unknown',
                'sunrise' => isset($sys['sunrise']) ? Carbon::createFromTimestamp($sys['sunrise']) : null,
                'sunset' => isset($sys['sunset']) ? Carbon::createFromTimestamp($sys['sunset']) : null,
                'dt' => Carbon::createFromTimestamp($weatherData['dt']),
                'raw_data' => $weatherData,
            ];

            return WeatherData::create($data);

        } catch (\Exception $e) {
            Log::error('Error al guardar datos meteorológicos', [
                'location_id' => $location->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return null;
        }
    }

    // Obtener y guardar datos para una ubicación
    public function fetchAndStoreWeatherData(Location $location): ?WeatherData
    {
        $weatherData = $this->getCurrentWeather($location);

        if ($weatherData) {
            return $this->storeWeatherData($location, $weatherData);
        }

        return null;
    }

    // Obtener y guardar datos para todas las ubicaciones activas
    public function fetchAllActiveLocations(): array
    {
        $results = [];
        $locations = Location::active()->get();

        foreach ($locations as $location) {
            $weatherData = $this->fetchAndStoreWeatherData($location);
            $results[] = [
                'location' => $location,
                'weather_data' => $weatherData,
                'success' => $weatherData !== null
            ];

            // Pequeña pausa para evitar sobrecargar la API
            sleep(1);
        }

        return $results;
    }
}
