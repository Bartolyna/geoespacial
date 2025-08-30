<?php

namespace App\Http\Controllers;

use App\Services\MemoryOptimizationService;
use App\Services\PostGISService;
use App\DataStructures\QuadTree;
use App\DataStructures\LRUCache;
use App\DataStructures\PriorityQueue;
use App\Models\Location;
use App\Models\WeatherData;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Controlador de demostración para estructuras de datos de optimización
 */
class MemoryOptimizationController extends Controller
{
    private MemoryOptimizationService $optimizationService;
    private PostGISService $postGISService;

    public function __construct(
        MemoryOptimizationService $optimizationService,
        PostGISService $postGISService
    ) {
        $this->optimizationService = $optimizationService;
        $this->postGISService = $postGISService;
    }

    /**
     * Demostración completa del sistema de optimización
     */
    public function demonstration(Request $request): JsonResponse
    {
        try {
            $demo = [
                'timestamp' => now()->toISOString(),
                'steps' => []
            ];

            // Paso 1: Inicializar sistema
            $demo['steps'][] = [
                'step' => 1,
                'name' => 'Inicialización del Sistema',
                'description' => 'Cargar datos iniciales en estructuras de memoria',
                'start_time' => microtime(true)
            ];

            $loadStats = $this->optimizationService->loadInitialData();
            $demo['steps'][0]['result'] = $loadStats;
            $demo['steps'][0]['duration_ms'] = round((microtime(true) - $demo['steps'][0]['start_time']) * 1000, 2);

            // Paso 2: Demostrar QuadTree
            $demo['steps'][] = $this->demonstrateQuadTree();

            // Paso 3: Demostrar LRU Cache
            $demo['steps'][] = $this->demonstrateLRUCache();

            // Paso 4: Demostrar Priority Queue
            $demo['steps'][] = $this->demonstratePriorityQueue();

            // Paso 5: Demostrar búsqueda optimizada
            $demo['steps'][] = $this->demonstrateOptimizedSearch();

            // Paso 6: Estadísticas finales
            $demo['steps'][] = [
                'step' => 6,
                'name' => 'Estadísticas del Sistema',
                'description' => 'Métricas de rendimiento y uso de memoria',
                'result' => $this->optimizationService->getSystemStats()
            ];

            return response()->json([
                'success' => true,
                'demonstration' => $demo
            ]);

        } catch (\Exception $e) {
            Log::error("Error in demonstration: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function demonstrateQuadTree(): array
    {
        $startTime = microtime(true);
        
        // Crear QuadTree de demostración
        $quadTree = new QuadTree(-180, -90, 180, 90, 100);
        
        // Insertar algunas ubicaciones de ejemplo
        $samplePoints = [
            ['lat' => 40.7128, 'lon' => -74.0060, 'name' => 'Nueva York'],
            ['lat' => 34.0522, 'lon' => -118.2437, 'name' => 'Los Ángeles'],
            ['lat' => 41.8781, 'lon' => -87.6298, 'name' => 'Chicago'],
            ['lat' => 29.7604, 'lon' => -95.3698, 'name' => 'Houston'],
            ['lat' => 33.4484, 'lon' => -112.0740, 'name' => 'Phoenix']
        ];

        foreach ($samplePoints as $point) {
            $quadTree->insert(
                $point['lon'],
                $point['lat'],
                ['name' => $point['name'], 'type' => 'city']
            );
        }

        // Realizar consultas de demostración
        $rangeQuery = $quadTree->queryRange(-120, 30, -70, 45); // Costa este
        $radiusQuery = $quadTree->queryRadius(-74.0060, 40.7128, 5); // Radio desde NYC

        return [
            'step' => 2,
            'name' => 'Demostración QuadTree',
            'description' => 'Indexación espacial eficiente para consultas geográficas',
            'start_time' => $startTime,
            'result' => [
                'points_inserted' => count($samplePoints),
                'range_query_results' => count($rangeQuery),
                'radius_query_results' => count($radiusQuery),
                'quadtree_stats' => $quadTree->getStats(),
                'sample_range_results' => $rangeQuery,
                'sample_radius_results' => $radiusQuery
            ],
            'duration_ms' => round((microtime(true) - $startTime) * 1000, 2)
        ];
    }

    private function demonstrateLRUCache(): array
    {
        $startTime = microtime(true);
        
        // Crear cache LRU de demostración
        $cache = LRUCache::createForSpatialQueries(5, 300); // Capacidad 5, TTL 5 min
        
        // Simular consultas espaciales
        $queries = [
            'spatial_query_1' => ['type' => 'radius', 'lat' => 40.7128, 'lon' => -74.0060, 'radius' => 10],
            'spatial_query_2' => ['type' => 'clustering', 'distance' => 50, 'min_points' => 2],
            'spatial_query_3' => ['type' => 'density', 'lat' => 34.0522, 'lon' => -118.2437, 'radius' => 25],
            'spatial_query_4' => ['type' => 'nearest', 'lat' => 41.8781, 'lon' => -87.6298, 'count' => 5],
            'spatial_query_5' => ['type' => 'polygon', 'bounds' => [[-120, 30], [-70, 45]]],
            'spatial_query_6' => ['type' => 'corridor', 'start' => [40.7128, -74.0060], 'end' => [34.0522, -118.2437]]
        ];

        $cacheOperations = [];
        
        // Insertar consultas en cache
        foreach ($queries as $key => $query) {
            $cache->putCompressed($key, $query);
            $cacheOperations[] = ['operation' => 'put', 'key' => $key, 'hit' => false];
        }

        // Simular accesos (algunos hits, algunos misses)
        $accessPattern = ['spatial_query_1', 'spatial_query_2', 'spatial_query_1', 'spatial_query_7', 'spatial_query_3'];
        
        foreach ($accessPattern as $key) {
            $result = $cache->getCompressed($key);
            $cacheOperations[] = [
                'operation' => 'get',
                'key' => $key,
                'hit' => $result !== null
            ];
        }

        return [
            'step' => 3,
            'name' => 'Demostración LRU Cache',
            'description' => 'Cache inteligente con TTL para consultas espaciales costosas',
            'start_time' => $startTime,
            'result' => [
                'cache_operations' => $cacheOperations,
                'cache_stats' => $cache->getStats(),
                'cached_keys' => $cache->keys()
            ],
            'duration_ms' => round((microtime(true) - $startTime) * 1000, 2)
        ];
    }

    private function demonstratePriorityQueue(): array
    {
        $startTime = microtime(true);
        
        // Crear cola de prioridad para eventos meteorológicos
        $eventQueue = PriorityQueue::createForWeatherEvents();
        
        // Simular eventos meteorológicos con diferentes severidades
        $weatherEvents = [
            ['event' => 'Tormenta severa', 'location' => 'Miami', 'severity' => 8.5],
            ['event' => 'Lluvia ligera', 'location' => 'Seattle', 'severity' => 2.0],
            ['event' => 'Huracán categoría 3', 'location' => 'Nueva Orleans', 'severity' => 9.8],
            ['event' => 'Nevada moderada', 'location' => 'Denver', 'severity' => 4.5],
            ['event' => 'Tornado F2', 'location' => 'Oklahoma City', 'severity' => 9.2],
            ['event' => 'Granizo grande', 'location' => 'Dallas', 'severity' => 6.7],
            ['event' => 'Viento fuerte', 'location' => 'Chicago', 'severity' => 5.8],
            ['event' => 'Neblina densa', 'location' => 'San Francisco', 'severity' => 3.2]
        ];

        $insertedEvents = [];
        foreach ($weatherEvents as $event) {
            $eventId = $eventQueue->insertWeatherEvent($event, $event['severity']);
            $insertedEvents[] = [
                'id' => $eventId,
                'event' => $event['event'],
                'severity' => $event['severity']
            ];
        }

        // Procesar eventos de alta prioridad primero
        $processedEvents = [];
        $highPriorityEvents = $eventQueue->extractByThreshold(7.0);
        
        foreach ($highPriorityEvents as $eventItem) {
            $processedEvents[] = [
                'id' => $eventItem->id,
                'event' => $eventItem->data['event'],
                'severity' => $eventItem->priority,
                'action' => 'emergency_response_triggered'
            ];
        }

        return [
            'step' => 4,
            'name' => 'Demostración Priority Queue',
            'description' => 'Procesamiento de eventos meteorológicos por severidad',
            'start_time' => $startTime,
            'result' => [
                'total_events_inserted' => count($insertedEvents),
                'high_priority_processed' => count($processedEvents),
                'remaining_in_queue' => $eventQueue->getSize(),
                'inserted_events' => $insertedEvents,
                'processed_events' => $processedEvents,
                'queue_stats' => $eventQueue->getStats()
            ],
            'duration_ms' => round((microtime(true) - $startTime) * 1000, 2)
        ];
    }

    private function demonstrateOptimizedSearch(): array
    {
        $startTime = microtime(true);
        
        try {
            // Demostrar búsqueda optimizada híbrida
            $searchCenter = [40.7128, -74.0060]; // Nueva York
            $radiusKm = 100;
            
            // Comparar rendimiento: PostGIS vs Optimizado
            $directStart = microtime(true);
            $directResults = $this->postGISService->findWithinRadius(
                $searchCenter[0],
                $searchCenter[1],
                $radiusKm
            );
            $directTime = (microtime(true) - $directStart) * 1000;

            $optimizedStart = microtime(true);
            $optimizedResults = $this->optimizationService->optimizedSpatialSearch(
                $searchCenter[0],
                $searchCenter[1],
                $radiusKm
            );
            $optimizedTime = (microtime(true) - $optimizedStart) * 1000;

            return [
                'step' => 5,
                'name' => 'Búsqueda Espacial Optimizada',
                'description' => 'Comparación entre PostGIS directo y búsqueda híbrida optimizada',
                'start_time' => $startTime,
                'result' => [
                    'search_center' => $searchCenter,
                    'radius_km' => $radiusKm,
                    'direct_postGIS' => [
                        'results_count' => count($directResults),
                        'execution_time_ms' => round($directTime, 2),
                        'method' => 'PostGIS_direct'
                    ],
                    'optimized_hybrid' => [
                        'results_count' => count($optimizedResults['locations'] ?? []),
                        'execution_time_ms' => round($optimizedTime, 2),
                        'method' => 'QuadTree + PostGIS + Cache',
                        'candidate_count' => $optimizedResults['candidate_count'] ?? 0,
                        'source' => $optimizedResults['source'] ?? 'unknown'
                    ],
                    'performance_improvement' => [
                        'time_saved_ms' => round($directTime - $optimizedTime, 2),
                        'improvement_percentage' => $directTime > 0 ? round((($directTime - $optimizedTime) / $directTime) * 100, 2) : 0
                    ]
                ],
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2)
            ];

        } catch (\Exception $e) {
            return [
                'step' => 5,
                'name' => 'Búsqueda Espacial Optimizada',
                'description' => 'Error en la demostración de búsqueda optimizada',
                'start_time' => $startTime,
                'error' => $e->getMessage(),
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2)
            ];
        }
    }

    /**
     * Endpoint para estadísticas en tiempo real
     */
    public function stats(): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'stats' => $this->optimizationService->getSystemStats(),
                'timestamp' => now()->toISOString()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Endpoint para optimización de memoria
     */
    public function optimize(): JsonResponse
    {
        try {
            $optimizationStats = $this->optimizationService->optimizeMemory();
            
            return response()->json([
                'success' => true,
                'optimization_stats' => $optimizationStats,
                'timestamp' => now()->toISOString()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Benchmark de estructuras de datos
     */
    public function benchmark(Request $request): JsonResponse
    {
        try {
            $iterations = $request->get('iterations', 1000);
            $benchmark = [
                'iterations' => $iterations,
                'timestamp' => now()->toISOString(),
                'tests' => []
            ];

            // Benchmark QuadTree
            $benchmark['tests']['quadtree'] = $this->benchmarkQuadTree($iterations);

            // Benchmark LRU Cache
            $benchmark['tests']['lru_cache'] = $this->benchmarkLRUCache($iterations);

            // Benchmark Priority Queue
            $benchmark['tests']['priority_queue'] = $this->benchmarkPriorityQueue($iterations);

            return response()->json([
                'success' => true,
                'benchmark' => $benchmark
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function benchmarkQuadTree(int $iterations): array
    {
        $quadTree = new QuadTree(-180, -90, 180, 90, $iterations);
        
        // Benchmark inserción
        $insertStart = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $quadTree->insert(
                rand(-180, 180), // lon
                rand(-90, 90),   // lat
                ['id' => $i, 'test' => true]
            );
        }
        $insertTime = (microtime(true) - $insertStart) * 1000;

        // Benchmark consultas
        $queryStart = microtime(true);
        for ($i = 0; $i < 100; $i++) {
            $quadTree->queryRadius(
                rand(-180, 180),
                rand(-90, 90),
                rand(1, 50)
            );
        }
        $queryTime = (microtime(true) - $queryStart) * 1000;

        return [
            'insert_time_ms' => round($insertTime, 2),
            'query_time_ms' => round($queryTime, 2),
            'inserts_per_second' => round($iterations / ($insertTime / 1000), 2),
            'queries_per_second' => round(100 / ($queryTime / 1000), 2),
            'memory_usage_mb' => round(memory_get_usage() / 1024 / 1024, 2)
        ];
    }

    private function benchmarkLRUCache(int $iterations): array
    {
        $cache = new LRUCache($iterations, 3600);
        
        // Benchmark inserción
        $insertStart = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $cache->put("key_$i", ['data' => "value_$i", 'timestamp' => time()]);
        }
        $insertTime = (microtime(true) - $insertStart) * 1000;

        // Benchmark acceso (50% hits esperados)
        $accessStart = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $key = "key_" . rand(0, $iterations * 2); // 50% miss rate
            $cache->get($key);
        }
        $accessTime = (microtime(true) - $accessStart) * 1000;

        return [
            'insert_time_ms' => round($insertTime, 2),
            'access_time_ms' => round($accessTime, 2),
            'inserts_per_second' => round($iterations / ($insertTime / 1000), 2),
            'accesses_per_second' => round($iterations / ($accessTime / 1000), 2),
            'cache_stats' => $cache->getStats()
        ];
    }

    private function benchmarkPriorityQueue(int $iterations): array
    {
        $queue = new PriorityQueue();
        
        // Benchmark inserción
        $insertStart = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $queue->insert(['id' => $i, 'test' => true], rand(1, 100) / 10.0);
        }
        $insertTime = (microtime(true) - $insertStart) * 1000;

        // Benchmark extracción
        $extractStart = microtime(true);
        $extracted = 0;
        while (!$queue->isEmpty() && $extracted < 100) {
            $queue->extract();
            $extracted++;
        }
        $extractTime = (microtime(true) - $extractStart) * 1000;

        return [
            'insert_time_ms' => round($insertTime, 2),
            'extract_time_ms' => round($extractTime, 2),
            'inserts_per_second' => round($iterations / ($insertTime / 1000), 2),
            'extracts_per_second' => round($extracted / ($extractTime / 1000), 2),
            'queue_stats' => $queue->getStats()
        ];
    }
}
