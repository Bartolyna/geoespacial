<?php

namespace App\Http\Controllers;

use App\Services\PostGISService;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class PostGISController extends Controller
{
    public function __construct(
        private PostGISService $postGISService
    ) {}

    /**
     * Obtener información de PostGIS
     */
    public function getInfo(): JsonResponse
    {
        Log::info('Obteniendo información de PostGIS', [
            'ip' => request()->ip(),
        ]);

        $info = $this->postGISService->getPostGISInfo();

        return response()->json([
            'status' => 'success',
            'data' => $info,
        ]);
    }

    /**
     * Buscar ubicaciones dentro de un radio
     */
    public function findWithinRadius(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius' => 'nullable|integer|min:100|max:100000', // 100m a 100km
        ]);

        Log::info('Búsqueda por radio con PostGIS', [
            'ip' => $request->ip(),
            'coordinates' => [
                'lat' => $validated['latitude'],
                'lng' => $validated['longitude']
            ],
            'radius' => $validated['radius'] ?? 1000,
        ]);

        $radius = $validated['radius'] ?? 1000; // 1km por defecto
        
        $locations = $this->postGISService->findLocationsWithinRadius(
            $validated['latitude'],
            $validated['longitude'],
            $radius
        );

        return response()->json([
            'status' => 'success',
            'data' => [
                'search_center' => [
                    'latitude' => $validated['latitude'],
                    'longitude' => $validated['longitude']
                ],
                'radius_meters' => $radius,
                'locations' => $locations,
                'count' => $locations->count()
            ],
        ]);
    }

    /**
     * Obtener ubicaciones más cercanas
     */
    public function getNearestLocations(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'limit' => 'nullable|integer|min:1|max:20',
        ]);

        $limit = $validated['limit'] ?? 5;

        $locations = $this->postGISService->getNearestLocations(
            $validated['latitude'],
            $validated['longitude'],
            $limit
        );

        return response()->json([
            'status' => 'success',
            'data' => [
                'search_center' => [
                    'latitude' => $validated['latitude'],
                    'longitude' => $validated['longitude']
                ],
                'limit' => $limit,
                'locations' => $locations,
                'count' => $locations->count()
            ],
        ]);
    }

    /**
     * Calcular distancia entre dos puntos
     */
    public function calculateDistance(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from_latitude' => 'required|numeric|between:-90,90',
            'from_longitude' => 'required|numeric|between:-180,180',
            'to_latitude' => 'required|numeric|between:-90,90',
            'to_longitude' => 'required|numeric|between:-180,180',
        ]);

        $distance = $this->postGISService->calculateDistance(
            $validated['from_latitude'],
            $validated['from_longitude'],
            $validated['to_latitude'],
            $validated['to_longitude']
        );

        return response()->json([
            'status' => 'success',
            'data' => [
                'from' => [
                    'latitude' => $validated['from_latitude'],
                    'longitude' => $validated['from_longitude']
                ],
                'to' => [
                    'latitude' => $validated['to_latitude'],
                    'longitude' => $validated['to_longitude']
                ],
                'distance_meters' => $distance,
                'distance_km' => round($distance / 1000, 2),
            ],
        ]);
    }

    /**
     * Buscar ubicaciones dentro de un polígono
     */
    public function findInPolygon(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'coordinates' => 'required|array|min:3',
            'coordinates.*.lat' => 'required|numeric|between:-90,90',
            'coordinates.*.lng' => 'required|numeric|between:-180,180',
        ]);

        $locations = $this->postGISService->findLocationsInPolygon($validated['coordinates']);

        return response()->json([
            'status' => 'success',
            'data' => [
                'polygon' => $validated['coordinates'],
                'locations' => $locations,
                'count' => $locations->count()
            ],
        ]);
    }

    /**
     * Obtener centro geográfico de ubicaciones
     */
    public function getCentroid(): JsonResponse
    {
        $locations = Location::active()->get();
        $centroid = $this->postGISService->getCentroid($locations);

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_locations' => $locations->count(),
                'centroid' => $centroid,
            ],
        ]);
    }

    /**
     * Crear buffer alrededor de una ubicación
     */
    public function createBuffer(Request $request, Location $location): JsonResponse
    {
        $validated = $request->validate([
            'radius' => 'required|integer|min:100|max:50000', // 100m a 50km
        ]);

        $buffer = $this->postGISService->createBuffer($location, $validated['radius']);

        return response()->json([
            'status' => 'success',
            'data' => [
                'location' => $location,
                'radius_meters' => $validated['radius'],
                'buffer_geojson' => $buffer,
            ],
        ]);
    }

    /**
     * Obtener estadísticas geográficas
     */
    public function getGeographicStats(): JsonResponse
    {
        Log::info('Obteniendo estadísticas geográficas', [
            'ip' => request()->ip(),
        ]);

        $stats = $this->postGISService->getGeographicStats();

        return response()->json([
            'status' => 'success',
            'data' => $stats,
        ]);
    }
}
