<?php

use App\Http\Controllers\GeospatialController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::prefix('geospatial')->group(function () {
    

    Route::get('/locations', [GeospatialController::class, 'getLocations']);
    Route::post('/locations', [GeospatialController::class, 'createLocation']);
    Route::get('/locations/{location}', [GeospatialController::class, 'getLocationWeather']);
    Route::put('/locations/{location}/update', [GeospatialController::class, 'updateLocationWeather']);
    Route::patch('/locations/{location}/toggle', [GeospatialController::class, 'toggleLocation']);
    Route::delete('/locations/{location}', [GeospatialController::class, 'deleteLocation']);

    Route::get('/summary', [GeospatialController::class, 'getSummary']);
    Route::get('/stats', [GeospatialController::class, 'getServiceStats']);
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
