<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Verificar que PostGIS esté habilitado
        DB::statement('CREATE EXTENSION IF NOT EXISTS postgis');
        
        Schema::table('locations', function (Blueprint $table) {
            // Agregar columna de punto geográfico usando PostGIS
            $table->geometry('geom', 'POINT', 4326)->nullable()->after('longitude');
            
            // Crear índice espacial para optimizar consultas geográficas
            $table->spatialIndex('geom');
        });
        
        // Crear función para actualizar automáticamente la geometría
        DB::statement("
            CREATE OR REPLACE FUNCTION update_location_geom()
            RETURNS TRIGGER AS $$
            BEGIN
                -- Actualizar geometría automáticamente cuando cambian lat/lng
                NEW.geom = ST_SetSRID(ST_MakePoint(NEW.longitude, NEW.latitude), 4326);
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ");
        
        // Crear trigger para actualización automática
        DB::statement("
            CREATE TRIGGER trigger_update_location_geom
                BEFORE INSERT OR UPDATE ON locations
                FOR EACH ROW
                EXECUTE FUNCTION update_location_geom();
        ");
        
        // Actualizar geometrías existentes
        DB::statement("
            UPDATE locations 
            SET geom = ST_SetSRID(ST_MakePoint(longitude, latitude), 4326)
            WHERE latitude IS NOT NULL AND longitude IS NOT NULL;
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar trigger y función
        DB::statement('DROP TRIGGER IF EXISTS trigger_update_location_geom ON locations');
        DB::statement('DROP FUNCTION IF EXISTS update_location_geom()');
        
        Schema::table('locations', function (Blueprint $table) {
            $table->dropSpatialIndex(['geom']);
            $table->dropColumn('geom');
        });
    }
};
