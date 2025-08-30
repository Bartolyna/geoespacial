# Estructuras de Datos de Optimización de Memoria

## Resumen

Este sistema implementa tres estructuras de datos avanzadas para optimizar el rendimiento de operaciones geoespaciales y de memoria:

1. **QuadTree** - Indexación espacial eficiente
2. **LRU Cache con TTL** - Cache inteligente con expiración
3. **Priority Queue (Heap)** - Cola de prioridad para procesamiento de eventos

## 1. QuadTree - Indexación Espacial

### Propósito
Estructura de datos espacial que subdivide recursivamente el espacio 2D para consultas geográficas eficientes.

### Características
- **Subdivisión automática** cuando se excede la capacidad
- **Consultas de rango** (queryRange) en tiempo O(log n)
- **Consultas de radio** (queryRadius) optimizadas
- **Estadísticas de rendimiento** integradas
- **Factory method** para crear desde ubicaciones existentes

### Uso Básico
```php
use App\DataStructures\QuadTree;

// Crear QuadTree para el mundo entero
$quadTree = new QuadTree(-180, -90, 180, 90, 100);

// Insertar puntos
$quadTree->insert($longitude, $latitude, $data);

// Consulta por rango
$points = $quadTree->queryRange($minLon, $minLat, $maxLon, $maxLat);

// Consulta por radio
$nearbyPoints = $quadTree->queryRadius($centerLon, $centerLat, $radiusKm);

// Obtener estadísticas
$stats = $quadTree->getStats();
```

### Casos de Uso
- **Búsquedas geográficas rápidas** antes de consultas PostGIS costosas
- **Filtrado de candidatos** para análisis espacial
- **Clustering geográfico** eficiente
- **Detección de vecinos** en tiempo real

## 2. LRU Cache con TTL

### Propósito
Cache inteligente que mantiene los elementos más recientemente usados y automáticamente expira datos antiguos.

### Características
- **Least Recently Used (LRU)** eviction policy
- **Time To Live (TTL)** configurable por cache
- **Compresión automática** para objetos grandes
- **Estadísticas detalladas** (hit rate, memoria, etc.)
- **Factory methods** para diferentes tipos de datos
- **Limpieza automática** de elementos expirados

### Uso Básico
```php
use App\DataStructures\LRUCache;

// Crear cache para consultas espaciales (30 min TTL)
$cache = LRUCache::createForSpatialQueries(200, 1800);

// Guardar resultado con compresión automática
$cache->putCompressed($key, $heavyQueryResult);

// Recuperar con descompresión automática
$result = $cache->getCompressed($key);

// Limpiar elementos expirados
$removed = $cache->cleanExpired();

// Obtener estadísticas
$stats = $cache->getStats();
```

### Factory Methods
```php
// Cache para resultados de LLM (2 horas TTL)
$llmCache = LRUCache::createForLLMResults(50, 7200);

// Cache para consultas espaciales (30 minutos TTL)
$spatialCache = LRUCache::createForSpatialQueries(200, 1800);

// Cache para datos meteorológicos (15 minutos TTL)
$weatherCache = LRUCache::createForWeatherData(100, 900);
```

### Casos de Uso
- **Resultados de consultas PostGIS costosas**
- **Análisis de clustering y densidad**
- **Respuestas de APIs externas**
- **Resultados de cálculos complejos**

## 3. Priority Queue (Max/Min Heap)

### Propósito
Cola de prioridad implementada como heap binario para procesamiento eficiente de eventos por importancia.

### Características
- **Max-Heap o Min-Heap** configurable
- **Inserción y extracción** en O(log n)
- **Actualización de prioridad** en tiempo real
- **Extracción por lotes** y por umbral
- **Búsqueda por ID** de elementos
- **Validación de integridad** del heap

### Uso Básico
```php
use App\DataStructures\PriorityQueue;

// Crear cola para eventos meteorológicos (Max-Heap)
$eventQueue = PriorityQueue::createForWeatherEvents();

// Insertar evento con severidad
$eventId = $eventQueue->insertWeatherEvent($weatherData, $severity);

// Extraer evento de mayor prioridad
$highestPriorityEvent = $eventQueue->extract();

// Extraer múltiples eventos de alta prioridad
$urgentEvents = $eventQueue->extractByThreshold(8.0);

// Actualizar prioridad de evento existente
$eventQueue->updatePriority($eventId, $newSeverity);
```

### Factory Methods
```php
// Cola para eventos meteorológicos (Max-Heap por severidad)
$weatherQueue = PriorityQueue::createForWeatherEvents();

// Cola para tareas de procesamiento (Max-Heap por prioridad)
$taskQueue = PriorityQueue::createForProcessingTasks();

// Cola para programación temporal (Min-Heap por tiempo)
$scheduleQueue = PriorityQueue::createForScheduling();
```

### Casos de Uso
- **Procesamiento de eventos meteorológicos** por severidad
- **Gestión de tareas** por prioridad
- **Alertas y notificaciones** urgentes
- **Programación de trabajos** por tiempo

## Servicio de Integración

### MemoryOptimizationService

Servicio que integra las tres estructuras para optimización completa:

```php
use App\Services\MemoryOptimizationService;

$optimizationService = new MemoryOptimizationService();

// Cargar datos iniciales
$loadStats = $optimizationService->loadInitialData();

// Búsqueda espacial optimizada (QuadTree + PostGIS + Cache)
$results = $optimizationService->optimizedSpatialSearch(
    $lat, $lon, $radiusKm, $filters
);

// Procesar eventos por prioridad
$processed = $optimizationService->processWeatherEvents($batchSize);

// Obtener estadísticas del sistema
$systemStats = $optimizationService->getSystemStats();

// Optimizar memoria
$optimizationStats = $optimizationService->optimizeMemory();
```

## API Endpoints

### Demostración Completa
```
GET /api/memory-optimization/demonstration
```
Ejecuta una demostración completa de las tres estructuras de datos con ejemplos prácticos.

### Estadísticas en Tiempo Real
```
GET /api/memory-optimization/stats
```
Obtiene estadísticas actuales de uso de memoria y rendimiento.

### Optimización Manual
```
POST /api/memory-optimization/optimize
```
Ejecuta optimización manual de memoria y limpieza de caches.

### Benchmark de Rendimiento
```
POST /api/memory-optimization/benchmark
Content-Type: application/json

{
    "iterations": 1000
}
```
Ejecuta pruebas de rendimiento en las estructuras de datos.

## Métricas de Rendimiento

### QuadTree
- **Inserción**: O(log n) promedio, O(n) peor caso
- **Consulta de rango**: O(log n + k) donde k = resultados
- **Consulta de radio**: O(log n + k)
- **Memoria**: O(n) donde n = número de puntos

### LRU Cache
- **Inserción**: O(1)
- **Búsqueda**: O(1)
- **Eviction**: O(1)
- **Memoria**: O(capacidad)

### Priority Queue
- **Inserción**: O(log n)
- **Extracción**: O(log n)
- **Peek**: O(1)
- **Actualización de prioridad**: O(n) búsqueda + O(log n) reorganización

## Configuración

### Parámetros del Sistema
```php
$config = [
    'spatial_index_capacity' => 1000,    // Capacidad QuadTree
    'query_cache_capacity' => 200,       // Capacidad cache consultas
    'query_cache_ttl' => 1800,          // TTL cache consultas (segundos)
    'result_cache_capacity' => 100,      // Capacidad cache resultados
    'result_cache_ttl' => 3600,         // TTL cache resultados (segundos)
    'auto_refresh_interval' => 300,      // Intervalo auto-refresh (segundos)
    'batch_size' => 50                   // Tamaño lote procesamiento eventos
];
```

## Mejores Prácticas

### QuadTree
1. **Definir límites apropiados** para tu región geográfica
2. **Ajustar capacidad** según densidad de datos
3. **Usar factory method** para inicialización desde datos existentes
4. **Monitorear estadísticas** para detectar hotspots

### LRU Cache
1. **Configurar TTL** según frecuencia de actualización de datos
2. **Usar compresión** para objetos grandes (>1KB)
3. **Generar keys consistentes** con parámetros ordenados
4. **Limpiar periódicamente** elementos expirados

### Priority Queue
1. **Definir escalas de prioridad** claras y consistentes
2. **Procesar por lotes** para eficiencia
3. **Usar umbrales** para filtrar eventos críticos
4. **Validar integridad** del heap periódicamente

## Casos de Uso Reales

### 1. Sistema de Alertas Meteorológicas
```php
// Configurar cola de eventos
$eventQueue = PriorityQueue::createForWeatherEvents();

// Procesar datos meteorológicos entrantes
foreach ($weatherUpdates as $update) {
    $severity = calculateSeverity($update);
    $eventQueue->insertWeatherEvent($update, $severity);
}

// Procesar eventos críticos primero
$criticalEvents = $eventQueue->extractByThreshold(8.0);
foreach ($criticalEvents as $event) {
    sendEmergencyAlert($event);
}
```

### 2. Búsqueda Geográfica Optimizada
```php
// Búsqueda híbrida con cache
$cacheKey = LRUCache::generateKey('near_search', [
    'lat' => $lat, 'lon' => $lon, 'radius' => $radius
]);

$results = $cache->get($cacheKey);
if ($results === null) {
    // Filtro inicial con QuadTree
    $candidates = $quadTree->queryRadius($lon, $lat, $radius);
    
    // Refinamiento con PostGIS
    $results = $postGIS->findWithinRadius($lat, $lon, $radius, $candidates);
    
    // Guardar en cache
    $cache->put($cacheKey, $results);
}
```

### 3. Análisis de Densidad Temporal
```php
// Cache para análisis costosos
$analysisCache = LRUCache::createForLLMResults(50, 3600);

$densityKey = LRUCache::generateKey('density_analysis', [
    'region' => $region, 'date' => $date, 'type' => $eventType
]);

$analysis = $analysisCache->getCompressed($densityKey);
if ($analysis === null) {
    $analysis = performDensityAnalysis($region, $date, $eventType);
    $analysisCache->putCompressed($densityKey, $analysis);
}
```

## Monitoreo y Debugging

### Estadísticas de Sistema
```php
$stats = $optimizationService->getSystemStats();

// Verificar hit rates
if ($stats['query_cache']['hit_rate'] < 70) {
    // Ajustar TTL o capacidad del cache
}

// Monitorear uso de memoria
if ($stats['memory_usage']['current_mb'] > 500) {
    $optimizationService->optimizeMemory();
}

// Verificar integridad del heap
$heapValidation = $eventQueue->validateHeap();
if (!$heapValidation['is_valid']) {
    Log::warning('Heap integrity violation', $heapValidation);
}
```

### Logs de Rendimiento
```php
// Log automático de operaciones lentas
Log::info('Spatial search completed', [
    'duration_ms' => $duration,
    'candidates' => $candidateCount,
    'results' => $resultCount,
    'cache_hit' => $cacheHit
]);
```

## Conclusión

Este sistema de estructuras de datos optimizadas proporciona:

- **Rendimiento mejorado** para consultas geoespaciales
- **Uso eficiente de memoria** con cache inteligente
- **Procesamiento prioritario** de eventos críticos
- **Escalabilidad** para sistemas de gran volumen
- **Monitoreo integrado** para optimización continua

Las tres estructuras trabajan en conjunto para crear un sistema robusto y eficiente para aplicaciones geoespaciales en tiempo real.
