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
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nombre de la ubicación
            $table->string('city'); // Ciudad
            $table->string('country'); // País
            $table->string('state')->nullable(); // Estado/Provincia
            $table->decimal('latitude', 10, 7); // Latitud con precisión
            $table->decimal('longitude', 10, 7); // Longitud con precisión
            $table->integer('openweather_id')->nullable(); // ID de OpenWeather
            $table->boolean('active')->default(true); // Si está activa para monitoreo
            $table->json('metadata')->nullable(); // Datos adicionales
            $table->timestamps();
            
            // Índices para optimizar búsquedas
            $table->index(['latitude', 'longitude']);
            $table->index('openweather_id');
            $table->index('active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
