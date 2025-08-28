<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('weather_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->onDelete('cascade');
            
            // Datos meteorológicos principales
            $table->decimal('temperature', 5, 2); // Temperatura actual
            $table->decimal('feels_like', 5, 2); // Sensación térmica
            $table->decimal('temp_min', 5, 2); // Temperatura mínima
            $table->decimal('temp_max', 5, 2); // Temperatura máxima
            $table->integer('pressure'); // Presión atmosférica
            $table->integer('humidity'); // Humedad
            $table->decimal('visibility', 8, 2)->nullable(); // Visibilidad
            
            // Viento
            $table->decimal('wind_speed', 5, 2)->nullable(); // Velocidad del viento
            $table->integer('wind_deg')->nullable(); // Dirección del viento
            $table->decimal('wind_gust', 5, 2)->nullable(); // Ráfagas
            
            // Precipitaciones
            $table->decimal('rain_1h', 5, 2)->nullable(); // Lluvia última hora
            $table->decimal('rain_3h', 5, 2)->nullable(); // Lluvia últimas 3 horas
            $table->decimal('snow_1h', 5, 2)->nullable(); // Nieve última hora
            $table->decimal('snow_3h', 5, 2)->nullable(); // Nieve últimas 3 horas
            
            // Nubes y condiciones
            $table->integer('clouds')->nullable(); // Nubosidad
            $table->string('weather_main'); // Condición principal
            $table->string('weather_description'); // Descripción detallada
            $table->string('weather_icon'); // Icono
            
            // Datos solares
            $table->timestamp('sunrise')->nullable();
            $table->timestamp('sunset')->nullable();
            
            // Metadatos
            $table->timestamp('dt'); // Timestamp de los datos
            $table->json('raw_data')->nullable(); // Datos completos de la API
            
            $table->timestamps();
            
            // Índices para optimizar consultas
            $table->index(['location_id', 'dt']);
            $table->index('dt');
            $table->index('weather_main');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weather_data');
    }
};
