#!/bin/bash
cat <<'EOF' > /var/www/html/app/Models/Location.php
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
}
EOF
EOF
