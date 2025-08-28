<?php

namespace App\Providers;

use App\Services\OpenWeatherService;
use App\Services\GeospatialWebSocketService;
use Illuminate\Support\ServiceProvider;

class GeospatialServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(OpenWeatherService::class, function ($app) {
            return new OpenWeatherService();
        });

        $this->app->singleton(GeospatialWebSocketService::class, function ($app) {
            return new GeospatialWebSocketService($app->make(OpenWeatherService::class));
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
