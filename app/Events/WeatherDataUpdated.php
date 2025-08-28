<?php

namespace App\Events;

use App\Models\Location;
use App\Models\WeatherData;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WeatherDataUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Location $location;
    public WeatherData $weatherData;

    public function __construct(Location $location, WeatherData $weatherData)
    {
        $this->location = $location;
        $this->weatherData = $weatherData;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('geospatial'),
            new Channel("location.{$this->location->id}"),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'weather.updated';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'location' => [
                'id' => $this->location->id,
                'name' => $this->location->name,
                'city' => $this->location->city,
                'country' => $this->location->country,
                'coordinates' => [
                    'latitude' => $this->location->latitude,
                    'longitude' => $this->location->longitude,
                ],
            ],
            'weather' => [
                'id' => $this->weatherData->id,
                'temperature' => $this->weatherData->temperature,
                'feels_like' => $this->weatherData->feels_like,
                'temp_min' => $this->weatherData->temp_min,
                'temp_max' => $this->weatherData->temp_max,
                'pressure' => $this->weatherData->pressure,
                'humidity' => $this->weatherData->humidity,
                'visibility' => $this->weatherData->visibility,
                'wind_speed' => $this->weatherData->wind_speed,
                'wind_deg' => $this->weatherData->wind_deg,
                'wind_direction' => $this->weatherData->wind_direction,
                'rain_1h' => $this->weatherData->rain_1h,
                'rain_3h' => $this->weatherData->rain_3h,
                'snow_1h' => $this->weatherData->snow_1h,
                'snow_3h' => $this->weatherData->snow_3h,
                'clouds' => $this->weatherData->clouds,
                'weather_main' => $this->weatherData->weather_main,
                'weather_description' => $this->weatherData->weather_description,
                'weather_icon' => $this->weatherData->weather_icon,
                'has_precipitation' => $this->weatherData->has_precipitation,
                'sunrise' => $this->weatherData->sunrise?->toISOString(),
                'sunset' => $this->weatherData->sunset?->toISOString(),
                'timestamp' => $this->weatherData->dt->toISOString(),
            ],
            'metadata' => [
                'updated_at' => now()->toISOString(),
                'source' => 'openweather',
            ],
        ];
    }
}
