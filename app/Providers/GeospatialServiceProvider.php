<?php

namespace App\Providers;

use App\Services\OpenWeatherService;
use App\Services\GeospatialWebSocketService;
use App\Services\PostGISService;
use App\Services\LLMService;
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

        $this->app->singleton(PostGISService::class, function ($app) {
            return new PostGISService();
        });

        $this->app->singleton(LLMService::class, function ($app) {
            return new LLMService();
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
