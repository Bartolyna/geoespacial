<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'city',
        'country',
        'state',
        'latitude',
        'longitude',
        'openweather_id',
        'active',
        'metadata',
        'geometry',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'active' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Relación con datos meteorológicos
     */
    public function weatherData(): HasMany
    {
        return $this->hasMany(WeatherData::class);
    }

    /**
     * Obtener los datos meteorológicos más recientes
     */
    public function latestWeatherData()
    {
        return $this->weatherData()->latest('dt')->first();
    }

    /**
     * Scope para ubicaciones activas
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Obtener coordenadas como string
     */
    public function getCoordinatesAttribute(): string
    {
        return "{$this->latitude},{$this->longitude}";
    }

    /**
     * Scope para búsquedas PostGIS por distancia
     */
    public function scopeWithinDistance($query, $latitude, $longitude, $radius)
    {
        return $query->whereRaw(
            'ST_DWithin(geometry, ST_SetSRID(ST_MakePoint(?, ?), 4326), ?)',
            [$longitude, $latitude, $radius]
        );
    }

    /**
     * Scope para ordenar por distancia
     */
    public function scopeOrderByDistance($query, $latitude, $longitude)
    {
        return $query->orderByRaw(
            'ST_Distance(geometry, ST_SetSRID(ST_MakePoint(?, ?), 4326))',
            [$longitude, $latitude]
        );
    }

    /**
     * Obtener distancia desde un punto
     */
    public function getDistanceFromAttribute($latitude, $longitude): float
    {
        $result = \DB::selectOne(
            'SELECT ST_Distance(geometry, ST_SetSRID(ST_MakePoint(?, ?), 4326)) as distance FROM locations WHERE id = ?',
            [$longitude, $latitude, $this->id]
        );

        return $result ? (float) $result->distance : 0.0;
    }
}
