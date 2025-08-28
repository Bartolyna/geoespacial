<?php

use Illuminate\Foundation\Application;
use App\Services\OpenWeatherService;
use App\Models\Location;

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Probando guardado de datos meteorológicos..." . PHP_EOL;

$service = new OpenWeatherService();
$location = Location::first();

if (!$location) {
    echo "No hay ubicaciones en la base de datos" . PHP_EOL;
    exit(1);
}

echo "Ubicación: {$location->name} ({$location->latitude}, {$location->longitude})" . PHP_EOL;

// Obtener datos de la API
$weatherData = $service->getCurrentWeather($location);

if (!$weatherData) {
    echo "No se pudieron obtener datos meteorológicos" . PHP_EOL;
    exit(1);
}

echo "Datos obtenidos de la API:" . PHP_EOL;
echo "- Temperatura: " . $weatherData['main']['temp'] . "°C" . PHP_EOL;
echo "- Descripción: " . $weatherData['weather'][0]['description'] . PHP_EOL;

// Intentar guardar los datos
try {
    $saved = $service->storeWeatherData($location, $weatherData);
    
    if ($saved) {
        echo "✅ Datos guardados exitosamente con ID: {$saved->id}" . PHP_EOL;
        echo "- Temperatura guardada: {$saved->temperature}°C" . PHP_EOL;
        echo "- Descripción guardada: {$saved->weather_description}" . PHP_EOL;
    } else {
        echo "❌ Error al guardar datos (retornó null)" . PHP_EOL;
        
        // Verificar los logs más recientes
        $logPath = '/var/www/html/storage/logs/laravel.log';
        if (file_exists($logPath)) {
            $logs = file_get_contents($logPath);
            $recentLogs = substr($logs, -2000); // Últimos 2000 caracteres
            echo "Logs recientes:" . PHP_EOL;
            echo $recentLogs . PHP_EOL;
        }
    }
} catch (Exception $e) {
    echo "❌ Excepción al guardar: " . $e->getMessage() . PHP_EOL;
    echo "Archivo: " . $e->getFile() . ":" . $e->getLine() . PHP_EOL;
    echo "Stack trace:" . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
}

// Verificar conteo total
$totalRecords = \App\Models\WeatherData::count();
echo "Total de registros en la base de datos: {$totalRecords}" . PHP_EOL;
