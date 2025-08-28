<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeatherData extends Model
{
    use HasFactory;

    protected $fillable = [
        'location_id',
        'dt',
        'temperature',
        'feels_like',
        'temp_min',
        'temp_max',
        'humidity',
        'pressure',
        'weather_main',
        'weather_description',
        'weather_icon',
        'wind_speed',
        'wind_deg',
        'wind_gust',
        'rain_1h',
        'rain_3h',
        'snow_1h',
        'snow_3h',
        'clouds',
        'visibility',
        'sunrise',
        'sunset',
        'raw_data',
    ];

    protected $casts = [
        'dt' => 'datetime',
        'temperature' => 'decimal:2',
        'feels_like' => 'decimal:2',
        'temp_min' => 'decimal:2',
        'temp_max' => 'decimal:2',
        'humidity' => 'integer',
        'pressure' => 'integer',
        'wind_speed' => 'decimal:2',
        'wind_deg' => 'integer',
        'wind_gust' => 'decimal:2',
        'rain_1h' => 'decimal:2',
        'rain_3h' => 'decimal:2',
        'snow_1h' => 'decimal:2',
        'snow_3h' => 'decimal:2',
        'clouds' => 'integer',
        'visibility' => 'decimal:2',
        'sunrise' => 'datetime',
        'sunset' => 'datetime',
        'raw_data' => 'array',
    ];


    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }


    public function scopeRecent($query)
    {
        return $query->where('dt', '>=', now()->subDay());
    }


    public function scopeToday($query)
    {
        return $query->whereDate('dt', now()->toDateString());
    }


    public function getTemperatureCelsiusAttribute(): string
    {
        return number_format($this->temperature, 1) . '°C';
    }

    public function getFormattedWeatherAttribute(): string
    {
        return ucfirst($this->weather_description);
    }
}
