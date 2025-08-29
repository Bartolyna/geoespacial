<?php

namespace App\Services;

use App\Models\Location;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class PostGISService
{
    /**
     * Encontrar ubicaciones dentro de un radio específico (en metros)
     */
    public function findLocationsWithinRadius(float $latitude, float $longitude, int $radiusMeters = 1000): Collection
    {
        return Location::whereRaw("
            ST_DWithin(
                geom,
                ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography,
                ?
            )
        ", [$longitude, $latitude, $radiusMeters])
        ->with('weatherData')
        ->get();
    }

    /**
     * Calcular la distancia entre dos puntos en metros
     */
    public function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $result = DB::selectOne("
            SELECT ST_Distance(
                ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography,
                ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography
            ) as distance
        ", [$lng1, $lat1, $lng2, $lat2]);

        return round($result->distance, 2);
    }

    /**
     * Obtener ubicaciones más cercanas a un punto
     */
    public function getNearestLocations(float $latitude, float $longitude, int $limit = 5): Collection
    {
        return Location::select([
            '*',
            DB::raw("
                ST_Distance(
                    geom::geography,
                    ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography
                ) as distance_meters
            ")
        ])
        ->whereNotNull('geom')
        ->orderBy('distance_meters')
        ->limit($limit)
        ->setBindings([$longitude, $latitude])
        ->get();
    }

    /**
     * Crear un área de búsqueda (polígono) y encontrar ubicaciones dentro
     */
    public function findLocationsInPolygon(array $coordinates): Collection
    {
        // Convertir coordenadas a formato WKT
        $points = collect($coordinates)->map(function ($coord) {
            return "{$coord['lng']} {$coord['lat']}";
        })->implode(', ');
        
        // Cerrar el polígono
        $firstPoint = $coordinates[0];
        $points .= ", {$firstPoint['lng']} {$firstPoint['lat']}";
        
        $polygon = "POLYGON(({$points}))";
        
        return Location::whereRaw("
            ST_Within(geom, ST_GeomFromText(?, 4326))
        ", [$polygon])
        ->with('weatherData')
        ->get();
    }

    /**
     * Obtener el centro geográfico de múltiples ubicaciones
     */
    public function getCentroid(Collection $locations): ?array
    {
        if ($locations->isEmpty()) {
            return null;
        }

        $locationIds = $locations->pluck('id')->toArray();
        
        $result = DB::selectOne("
            SELECT 
                ST_X(ST_Centroid(ST_Collect(geom))) as longitude,
                ST_Y(ST_Centroid(ST_Collect(geom))) as latitude
            FROM locations 
            WHERE id = ANY(?) AND geom IS NOT NULL
        ", ['{' . implode(',', $locationIds) . '}']);

        if (!$result) {
            return null;
        }

        return [
            'latitude' => round($result->latitude, 6),
            'longitude' => round($result->longitude, 6)
        ];
    }

    /**
     * Crear un buffer (área circular) alrededor de una ubicación
     */
    public function createBuffer(Location $location, int $radiusMeters): array
    {
        $result = DB::selectOne("
            SELECT ST_AsGeoJSON(
                ST_Transform(
                    ST_Buffer(geom::geography, ?)::geometry,
                    4326
                )
            ) as geojson
        ", [$radiusMeters]);

        return json_decode($result->geojson, true);
    }

    /**
     * Verificar si PostGIS está disponible
     */
    public function isPostGISAvailable(): bool
    {
        try {
            $result = DB::selectOne("SELECT postgis_version() as version");
            return !empty($result->version);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Obtener información de PostGIS
     */
    public function getPostGISInfo(): array
    {
        try {
            $version = DB::selectOne("SELECT postgis_version() as version");
            $libVersion = DB::selectOne("SELECT postgis_lib_version() as lib_version");
            
            $extensions = DB::select("
                SELECT name, installed_version 
                FROM pg_available_extensions 
                WHERE name LIKE 'postgis%' AND installed_version IS NOT NULL
            ");

            return [
                'available' => true,
                'version' => $version->version ?? null,
                'lib_version' => $libVersion->lib_version ?? null,
                'extensions' => collect($extensions)->mapWithKeys(function ($ext) {
                    return [$ext->name => $ext->installed_version];
                })->toArray()
            ];
        } catch (\Exception $e) {
            return [
                'available' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Generar estadísticas geográficas
     */
    public function getGeographicStats(): array
    {
        try {
            $result = DB::selectOne("
                SELECT 
                    COUNT(*) as total_locations,
                    ST_Extent(geom) as bounding_box,
                    ST_AsText(ST_Centroid(ST_Collect(geom))) as center_point
                FROM locations 
                WHERE geom IS NOT NULL
            ");

            $centerCoords = null;
            if ($result->center_point) {
                preg_match('/POINT\(([^)]+)\)/', $result->center_point, $matches);
                if (isset($matches[1])) {
                    [$lng, $lat] = explode(' ', $matches[1]);
                    $centerCoords = [
                        'latitude' => round(floatval($lat), 6),
                        'longitude' => round(floatval($lng), 6)
                    ];
                }
            }

            return [
                'total_locations' => $result->total_locations,
                'bounding_box' => $result->bounding_box,
                'center_point' => $centerCoords
            ];
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }
}
