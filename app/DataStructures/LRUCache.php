<?php

namespace App\DataStructures;

/**
 * Nodo del Cache LRU
 */
class LRUCacheNode
{
    public string $key;
    public mixed $value;
    public int $timestamp;
    public int $accessCount = 0;
    public ?LRUCacheNode $prev = null;
    public ?LRUCacheNode $next = null;

    public function __construct(string $key, mixed $value, int $timestamp)
    {
        $this->key = $key;
        $this->value = $value;
        $this->timestamp = $timestamp;
    }

    public function isExpired(int $ttl): bool
    {
        return (time() - $this->timestamp) > $ttl;
    }
}

/**
 * Cache LRU (Least Recently Used) con TTL configurable
 * Optimizado para resultados de LLM y consultas pesadas
 */
class LRUCache
{
    private array $cache = [];
    private int $capacity;
    private int $ttl; // Time To Live en segundos
    private LRUCacheNode $head;
    private LRUCacheNode $tail;
    private int $size = 0;
    private array $stats = [
        'hits' => 0,
        'misses' => 0,
        'evictions' => 0,
        'ttl_expiries' => 0,
        'total_requests' => 0,
        'memory_usage' => 0
    ];

    public function __construct(int $capacity = 100, int $ttl = 3600)
    {
        $this->capacity = max(1, $capacity);
        $this->ttl = max(1, $ttl);
        
        // Crear nodos dummy para head y tail
        $this->head = new LRUCacheNode('head', null, 0);
        $this->tail = new LRUCacheNode('tail', null, 0);
        $this->head->next = $this->tail;
        $this->tail->prev = $this->head;
    }

    public function get(string $key): mixed
    {
        $this->stats['total_requests']++;
        
        if (!isset($this->cache[$key])) {
            $this->stats['misses']++;
            return null;
        }

        $node = $this->cache[$key];
        
        // Verificar TTL
        if ($node->isExpired($this->ttl)) {
            $this->stats['ttl_expiries']++;
            $this->remove($key);
            return null;
        }

        $this->stats['hits']++;
        $node->accessCount++;
        
        // Mover al frente (más reciente)
        $this->moveToHead($node);
        
        return $node->value;
    }

    public function put(string $key, mixed $value): void
    {
        if (isset($this->cache[$key])) {
            // Actualizar valor existente
            $node = $this->cache[$key];
            $node->value = $value;
            $node->timestamp = time();
            $this->moveToHead($node);
        } else {
            // Nuevo nodo
            $newNode = new LRUCacheNode($key, $value, time());
            
            if ($this->size >= $this->capacity) {
                // Eviction: remover el menos usado
                $this->removeTail();
            }
            
            $this->cache[$key] = $newNode;
            $this->addToHead($newNode);
            $this->size++;
        }
        
        $this->updateMemoryStats();
    }

    public function remove(string $key): bool
    {
        if (!isset($this->cache[$key])) {
            return false;
        }

        $node = $this->cache[$key];
        $this->removeNode($node);
        unset($this->cache[$key]);
        $this->size--;
        
        return true;
    }

    public function clear(): void
    {
        $this->cache = [];
        $this->size = 0;
        $this->head->next = $this->tail;
        $this->tail->prev = $this->head;
        $this->resetStats();
    }

    public function cleanExpired(): int
    {
        $removed = 0;
        $current = $this->head->next;
        
        while ($current !== $this->tail) {
            $next = $current->next;
            
            if ($current->isExpired($this->ttl)) {
                $this->remove($current->key);
                $removed++;
                $this->stats['ttl_expiries']++;
            }
            
            $current = $next;
        }
        
        return $removed;
    }

    private function addToHead(LRUCacheNode $node): void
    {
        $node->prev = $this->head;
        $node->next = $this->head->next;
        $this->head->next->prev = $node;
        $this->head->next = $node;
    }

    private function removeNode(LRUCacheNode $node): void
    {
        $node->prev->next = $node->next;
        $node->next->prev = $node->prev;
    }

    private function moveToHead(LRUCacheNode $node): void
    {
        $this->removeNode($node);
        $this->addToHead($node);
    }

    private function removeTail(): void
    {
        $lastNode = $this->tail->prev;
        if ($lastNode !== $this->head) {
            $this->removeNode($lastNode);
            unset($this->cache[$lastNode->key]);
            $this->size--;
            $this->stats['evictions']++;
        }
    }

    private function updateMemoryStats(): void
    {
        $this->stats['memory_usage'] = memory_get_usage();
    }

    private function resetStats(): void
    {
        $this->stats = [
            'hits' => 0,
            'misses' => 0,
            'evictions' => 0,
            'ttl_expiries' => 0,
            'total_requests' => 0,
            'memory_usage' => 0
        ];
    }

    public function getStats(): array
    {
        $hitRate = $this->stats['total_requests'] > 0 
            ? round(($this->stats['hits'] / $this->stats['total_requests']) * 100, 2)
            : 0;

        return array_merge($this->stats, [
            'size' => $this->size,
            'capacity' => $this->capacity,
            'ttl' => $this->ttl,
            'hit_rate' => $hitRate,
            'efficiency' => $this->calculateEfficiency(),
            'memory_usage_mb' => round($this->stats['memory_usage'] / 1024 / 1024, 2)
        ]);
    }

    private function calculateEfficiency(): float
    {
        if ($this->capacity === 0) return 0;
        return round(($this->size / $this->capacity) * 100, 2);
    }

    public function has(string $key): bool
    {
        if (!isset($this->cache[$key])) {
            return false;
        }
        
        return !$this->cache[$key]->isExpired($this->ttl);
    }

    public function keys(): array
    {
        $keys = [];
        $current = $this->head->next;
        
        while ($current !== $this->tail) {
            if (!$current->isExpired($this->ttl)) {
                $keys[] = $current->key;
            }
            $current = $current->next;
        }
        
        return $keys;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getCapacity(): int
    {
        return $this->capacity;
    }

    public function getTTL(): int
    {
        return $this->ttl;
    }

    public function setTTL(int $ttl): void
    {
        $this->ttl = max(1, $ttl);
    }

    public function setCapacity(int $capacity): void
    {
        $oldCapacity = $this->capacity;
        $this->capacity = max(1, $capacity);
        
        // Si la nueva capacidad es menor, remover elementos
        if ($this->capacity < $oldCapacity) {
            while ($this->size > $this->capacity) {
                $this->removeTail();
            }
        }
    }

    /**
     * Crear cache optimizado para diferentes tipos de datos
     */
    public static function createForLLMResults(int $capacity = 50, int $ttl = 7200): self
    {
        // Cache optimizado para resultados de LLM (2 horas TTL)
        return new self($capacity, $ttl);
    }

    public static function createForSpatialQueries(int $capacity = 200, int $ttl = 1800): self
    {
        // Cache optimizado para consultas espaciales (30 minutos TTL)
        return new self($capacity, $ttl);
    }

    public static function createForWeatherData(int $capacity = 100, int $ttl = 900): self
    {
        // Cache optimizado para datos meteorológicos (15 minutos TTL)
        return new self($capacity, $ttl);
    }

    /**
     * Crear key único para consultas complejas
     */
    public static function generateKey(string $prefix, array $params): string
    {
        ksort($params); // Ordenar para consistencia
        return $prefix . ':' . md5(serialize($params));
    }

    /**
     * Guardar con compresión automática para objetos grandes
     */
    public function putCompressed(string $key, mixed $value): void
    {
        $serialized = serialize($value);
        
        // Comprimir si el valor es grande (> 1KB)
        if (strlen($serialized) > 1024) {
            $compressed = gzcompress($serialized, 6);
            $this->put($key, ['compressed' => true, 'data' => $compressed]);
        } else {
            $this->put($key, ['compressed' => false, 'data' => $value]);
        }
    }

    /**
     * Obtener con descompresión automática
     */
    public function getCompressed(string $key): mixed
    {
        $result = $this->get($key);
        
        if ($result === null) {
            return null;
        }
        
        if (is_array($result) && isset($result['compressed'])) {
            if ($result['compressed']) {
                $decompressed = gzuncompress($result['data']);
                return unserialize($decompressed);
            } else {
                return $result['data'];
            }
        }
        
        return $result;
    }
}
