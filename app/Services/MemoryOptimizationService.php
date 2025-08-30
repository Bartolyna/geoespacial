<?php

namespace App\Services;

use App\DataStructures\QuadTree;
use App\DataStructures\LRUCache;
use App\DataStructures\PriorityQueue;
use App\Models\Location;
use App\Models\WeatherData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Servicio de Optimización de Memoria para Operaciones Geoespaciales
 * Integra QuadTree, LRU Cache y Priority Queue con PostGIS
 */
class MemoryOptimizationService
{
    private QuadTree $spatialIndex;
    private LRUCache $queryCache;
    private LRUCache $resultCache;
    private PriorityQueue $eventQueue;
    private PostGISService $postGISService;
    
    private array $config = [
        'spatial_index_capacity' => 1000,
        'query_cache_capacity' => 200,
        'query_cache_ttl' => 1800, // 30 minutos
        'result_cache_capacity' => 100,
        'result_cache_ttl' => 3600, // 1 hora
        'auto_refresh_interval' => 300, // 5 minutos
        'batch_size' => 50
    ];

    public function __construct(PostGISService $postGISService = null)
    {
        $this->postGISService = $postGISService ?? new PostGISService();
        $this->initializeDataStructures();
    }

    private function initializeDataStructures(): void
    {
        // Inicializar QuadTree para indexación espacial
        $this->spatialIndex = new QuadTree(
            -180, -90, 180, 90, // Rango mundial
            $this->config['spatial_index_capacity']
        );

        // Cache para consultas SQL optimizadas
        $this->queryCache = LRUCache::createForSpatialQueries(
            $this->config['query_cache_capacity'],
            $this->config['query_cache_ttl']
        );

        // Cache para resultados de análisis complejos
        $this->resultCache = LRUCache::createForLLMResults(
            $this->config['result_cache_capacity'],
            $this->config['result_cache_ttl']
        );

        // Cola de prioridad para eventos meteorológicos
        $this->eventQueue = PriorityQueue::createForWeatherEvents();
    }

    /**
     * Cargar datos iniciales en las estructuras de memoria
     */
    public function loadInitialData(): array
    {
        try {
            $stats = ['loaded' => 0, 'errors' => 0, 'timing' => []];
            $startTime = microtime(true);

            // Cargar ubicaciones en QuadTree
            $locations = Location::with('weatherData')->get();
            
            foreach ($locations as $location) {
                try {
                    $this->spatialIndex->insert(
                        $location->longitude,
                        $location->latitude,
                        [
                            'id' => $location->id,
                            'name' => $location->name,
                            'type' => $location->type,
                            'weather_data_count' => $location->weatherData->count()
                        ]
                    );
                    $stats['loaded']++;
                } catch (\Exception $e) {
                    $stats['errors']++;
                    Log::warning("Error loading location {$location->id}: " . $e->getMessage());
                }
            }

            $stats['timing']['data_load'] = round((microtime(true) - $startTime) * 1000, 2);

            // Precargar consultas comunes
            $this->preloadCommonQueries();
            $stats['timing']['preload_queries'] = round((microtime(true) - $startTime) * 1000, 2);

            return $stats;
        } catch (\Exception $e) {
            Log::error("Error in loadInitialData: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Búsqueda espacial optimizada con cache y QuadTree
     */
    public function optimizedSpatialSearch(
        float $centerLat,
        float $centerLon,
        float $radiusKm,
        array $filters = []
    ): array {
        $cacheKey = LRUCache::generateKey('spatial_search', [
            'lat' => $centerLat,
            'lon' => $centerLon,
            'radius' => $radiusKm,
            'filters' => $filters
        ]);

        // Verificar cache primero
        $cachedResult = $this->resultCache->getCompressed($cacheKey);
        if ($cachedResult !== null) {
            return $cachedResult;
        }

        try {
            // Búsqueda inicial con QuadTree
            $candidatePoints = $this->spatialIndex->queryRadius(
                $centerLon,
                $centerLat,
                $radiusKm / 111 // Conversión aproximada km a grados
            );

            if (empty($candidatePoints)) {
                $result = ['locations' => [], 'source' => 'quadtree_empty'];
                $this->resultCache->putCompressed($cacheKey, $result);
                return $result;
            }

            // Extracción de IDs para consulta PostGIS
            $locationIds = array_map(fn($point) => $point['data']['id'], $candidatePoints);

            // Consulta PostGIS refinada
            $refinedResults = $this->postGISService->findWithinRadius(
                $centerLat,
                $centerLon,
                $radiusKm,
                array_merge($filters, ['location_ids' => $locationIds])
            );

            $result = [
                'locations' => $refinedResults,
                'candidate_count' => count($candidatePoints),
                'final_count' => count($refinedResults),
                'source' => 'optimized_hybrid',
                'cache_key' => $cacheKey
            ];

            // Guardar en cache con compresión
            $this->resultCache->putCompressed($cacheKey, $result);

            return $result;
        } catch (\Exception $e) {
            Log::error("Error in optimizedSpatialSearch: " . $e->getMessage());
            
            // Fallback a PostGIS directo
            $fallbackResult = $this->postGISService->findWithinRadius(
                $centerLat,
                $centerLon,
                $radiusKm,
                $filters
            );

            return [
                'locations' => $fallbackResult,
                'source' => 'postGIS_fallback',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Análisis de clustering optimizado con cache
     */
    public function optimizedClustering(float $distanceKm = 50, int $minPoints = 2): array
    {
        $cacheKey = LRUCache::generateKey('clustering', [
            'distance' => $distanceKm,
            'min_points' => $minPoints
        ]);

        $cachedResult = $this->queryCache->getCompressed($cacheKey);
        if ($cachedResult !== null) {
            return $cachedResult;
        }

        try {
            $result = $this->postGISService->performSpatialClustering($distanceKm, $minPoints);
            $this->queryCache->putCompressed($cacheKey, $result);
            return $result;
        } catch (\Exception $e) {
            Log::error("Error in optimizedClustering: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Procesar eventos meteorológicos por prioridad
     */
    public function processWeatherEvents(int $batchSize = null): array
    {
        $batchSize = $batchSize ?? $this->config['batch_size'];
        $processedEvents = [];
        
        try {
            $highPriorityEvents = $this->eventQueue->extractBatch($batchSize);
            
            foreach ($highPriorityEvents as $eventItem) {
                $weatherData = $eventItem->data;
                
                // Procesar según severidad
                $processedEvent = $this->processWeatherEvent($weatherData, $eventItem->priority);
                $processedEvents[] = $processedEvent;
                
                // Actualizar índice espacial si es necesario
                if (isset($weatherData['location_id'])) {
                    $this->updateSpatialIndex($weatherData);
                }
            }
            
            return [
                'processed_count' => count($processedEvents),
                'events' => $processedEvents,
                'queue_size_remaining' => $this->eventQueue->getSize()
            ];
        } catch (\Exception $e) {
            Log::error("Error processing weather events: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Agregar evento meteorológico a la cola de prioridad
     */
    public function queueWeatherEvent(array $weatherData, float $severity = null): string
    {
        try {
            // Calcular severidad automáticamente si no se proporciona
            if ($severity === null) {
                $severity = $this->calculateEventSeverity($weatherData);
            }
            
            return $this->eventQueue->insertWeatherEvent($weatherData, $severity);
        } catch (\Exception $e) {
            Log::error("Error queuing weather event: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtener estadísticas completas del sistema
     */
    public function getSystemStats(): array
    {
        return [
            'spatial_index' => $this->spatialIndex->getStats(),
            'query_cache' => $this->queryCache->getStats(),
            'result_cache' => $this->resultCache->getStats(),
            'event_queue' => $this->eventQueue->getStats(),
            'memory_usage' => [
                'current_mb' => round(memory_get_usage() / 1024 / 1024, 2),
                'peak_mb' => round(memory_get_peak_usage() / 1024 / 1024, 2)
            ],
            'config' => $this->config
        ];
    }

    /**
     * Limpiar datos expirados y optimizar memoria
     */
    public function optimizeMemory(): array
    {
        $stats = [
            'cache_cleaned' => 0,
            'memory_before_mb' => round(memory_get_usage() / 1024 / 1024, 2),
            'memory_after_mb' => 0
        ];

        try {
            // Limpiar caches expirados
            $stats['cache_cleaned'] += $this->queryCache->cleanExpired();
            $stats['cache_cleaned'] += $this->resultCache->cleanExpired();
            
            // Optimizar QuadTree (reimplementar si es necesario)
            if ($this->spatialIndex->getStats()['point_count'] > $this->config['spatial_index_capacity']) {
                $this->rebuildSpatialIndex();
            }
            
            // Forzar garbage collection
            gc_collect_cycles();
            
            $stats['memory_after_mb'] = round(memory_get_usage() / 1024 / 1024, 2);
            $stats['memory_freed_mb'] = $stats['memory_before_mb'] - $stats['memory_after_mb'];
            
            return $stats;
        } catch (\Exception $e) {
            Log::error("Error optimizing memory: " . $e->getMessage());
            throw $e;
        }
    }

    private function preloadCommonQueries(): void
    {
        try {
            // Consultas comunes que pueden beneficiarse del cache
            $commonQueries = [
                ['type' => 'density', 'radius' => 10],
                ['type' => 'density', 'radius' => 25],
                ['type' => 'clustering', 'distance' => 50, 'min_points' => 2],
                ['type' => 'clustering', 'distance' => 100, 'min_points' => 3]
            ];

            foreach ($commonQueries as $query) {
                if ($query['type'] === 'clustering') {
                    $this->optimizedClustering($query['distance'], $query['min_points']);
                }
            }
        } catch (\Exception $e) {
            Log::warning("Error preloading queries: " . $e->getMessage());
        }
    }

    private function processWeatherEvent(array $weatherData, float $priority): array
    {
        // Procesar evento según prioridad y tipo
        $processed = [
            'event_id' => $weatherData['id'] ?? uniqid(),
            'priority' => $priority,
            'processed_at' => time(),
            'actions_taken' => []
        ];

        // Acciones basadas en severidad
        if ($priority > 8.0) {
            $processed['actions_taken'][] = 'emergency_alert_sent';
            $processed['actions_taken'][] = 'spatial_analysis_triggered';
        } elseif ($priority > 5.0) {
            $processed['actions_taken'][] = 'warning_issued';
        }

        return $processed;
    }

    private function calculateEventSeverity(array $weatherData): float
    {
        $severity = 0.0;

        // Factores de severidad
        if (isset($weatherData['temperature'])) {
            $temp = floatval($weatherData['temperature']);
            if ($temp > 40 || $temp < -20) $severity += 3.0;
            elseif ($temp > 35 || $temp < -10) $severity += 2.0;
        }

        if (isset($weatherData['wind_speed'])) {
            $wind = floatval($weatherData['wind_speed']);
            if ($wind > 120) $severity += 4.0; // Huracán
            elseif ($wind > 88) $severity += 3.0; // Tormenta severa
            elseif ($wind > 50) $severity += 2.0;
        }

        if (isset($weatherData['precipitation'])) {
            $precip = floatval($weatherData['precipitation']);
            if ($precip > 100) $severity += 3.0;
            elseif ($precip > 50) $severity += 2.0;
        }

        return min(10.0, max(0.0, $severity));
    }

    private function updateSpatialIndex(array $weatherData): void
    {
        if (isset($weatherData['location_id'])) {
            try {
                $location = Location::find($weatherData['location_id']);
                if ($location) {
                    // Actualizar datos en el índice espacial
                    $updatedData = [
                        'id' => $location->id,
                        'name' => $location->name,
                        'type' => $location->type,
                        'last_weather_update' => time(),
                        'latest_severity' => $this->calculateEventSeverity($weatherData)
                    ];
                    
                    // El QuadTree actual no soporta actualización directa,
                    // así que podríamos reimplementar o reconstruir si es necesario
                }
            } catch (\Exception $e) {
                Log::warning("Error updating spatial index: " . $e->getMessage());
            }
        }
    }

    private function rebuildSpatialIndex(): void
    {
        try {
            $oldStats = $this->spatialIndex->getStats();
            
            // Crear nuevo QuadTree
            $newQuadTree = new QuadTree(
                -180, -90, 180, 90,
                $this->config['spatial_index_capacity']
            );
            
            // Recargar solo las ubicaciones más recientes/relevantes
            $recentLocations = Location::with('weatherData')
                ->whereHas('weatherData', function($query) {
                    $query->where('created_at', '>', now()->subDays(7));
                })
                ->limit($this->config['spatial_index_capacity'])
                ->get();
            
            foreach ($recentLocations as $location) {
                $newQuadTree->insert(
                    $location->longitude,
                    $location->latitude,
                    [
                        'id' => $location->id,
                        'name' => $location->name,
                        'type' => $location->type,
                        'relevance_score' => $location->weatherData->count()
                    ]
                );
            }
            
            $this->spatialIndex = $newQuadTree;
            
            Log::info("Spatial index rebuilt", [
                'old_count' => $oldStats['point_count'],
                'new_count' => $this->spatialIndex->getStats()['point_count']
            ]);
        } catch (\Exception $e) {
            Log::error("Error rebuilding spatial index: " . $e->getMessage());
        }
    }

    public function setConfig(array $config): void
    {
        $this->config = array_merge($this->config, $config);
    }

    public function getConfig(): array
    {
        return $this->config;
    }
}
