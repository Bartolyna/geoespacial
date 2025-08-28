<?php
echo 'Probando API directamente...' . PHP_EOL;

require_once __DIR__ . '/vendor/autoload.php';

// Cargar variables de entorno
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

$apiKey = $_ENV['WEATHER_API_KEY'] ?? null;
if (!$apiKey) {
    echo 'API Key no encontrada' . PHP_EOL;
    exit(1);
}
echo 'API Key presente: ' . substr($apiKey, 0, 8) . '...' . PHP_EOL;

// Coordenadas de Ciudad de México
$lat = 19.4285;
$lon = -99.1277;
$url = "https://api.openweathermap.org/data/2.5/weather?lat={$lat}&lon={$lon}&appid={$apiKey}&units=metric&lang=es";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo 'HTTP Code: ' . $httpCode . PHP_EOL;
if ($response) {
    $data = json_decode($response, true);
    if (isset($data['main'])) {
        echo 'Temperatura: ' . $data['main']['temp'] . '°C' . PHP_EOL;
        echo 'Descripción: ' . $data['weather'][0]['description'] . PHP_EOL;
        echo 'API funcionando correctamente' . PHP_EOL;
    } else {
        echo 'Error en respuesta: ' . $response . PHP_EOL;
    }
} else {
    echo 'No se pudo conectar a la API' . PHP_EOL;
}
