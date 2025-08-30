<?php

namespace App\DataStructures;

/**
 * Elemento de la Cola de Prioridad
 */
class PriorityQueueItem
{
    public mixed $data;
    public float $priority;
    public int $timestamp;
    public string $id;
    public array $metadata;

    public function __construct(
        mixed $data, 
        float $priority, 
        string $id = null, 
        array $metadata = []
    ) {
        $this->data = $data;
        $this->priority = $priority;
        $this->timestamp = time();
        $this->id = $id ?? uniqid('pq_', true);
        $this->metadata = $metadata;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'data' => $this->data,
            'priority' => $this->priority,
            'timestamp' => $this->timestamp,
            'metadata' => $this->metadata
        ];
    }
}

/**
 * Cola de Prioridad implementada como Max-Heap
 * Optimizada para procesar eventos por magnitud/severidad
 */
class PriorityQueue
{
    private array $heap = [];
    private int $size = 0;
    private bool $isMaxHeap;
    private array $stats = [
        'total_inserted' => 0,
        'total_extracted' => 0,
        'peak_size' => 0,
        'current_size' => 0,
        'average_priority' => 0,
        'priority_range' => ['min' => null, 'max' => null]
    ];

    public function __construct(bool $isMaxHeap = true)
    {
        $this->isMaxHeap = $isMaxHeap;
    }

    /**
     * Insertar elemento en la cola
     */
    public function insert(mixed $data, float $priority, string $id = null, array $metadata = []): string
    {
        $item = new PriorityQueueItem($data, $priority, $id, $metadata);
        
        $this->heap[$this->size] = $item;
        $this->heapifyUp($this->size);
        $this->size++;
        
        $this->updateStats();
        
        return $item->id;
    }

    /**
     * Extraer elemento con mayor/menor prioridad
     */
    public function extract(): ?PriorityQueueItem
    {
        if ($this->isEmpty()) {
            return null;
        }

        $root = $this->heap[0];
        $this->heap[0] = $this->heap[$this->size - 1];
        unset($this->heap[$this->size - 1]);
        $this->size--;
        
        if (!$this->isEmpty()) {
            $this->heapifyDown(0);
        }
        
        $this->stats['total_extracted']++;
        $this->stats['current_size'] = $this->size;
        
        return $root;
    }

    /**
     * Ver el elemento de mayor/menor prioridad sin extraerlo
     */
    public function peek(): ?PriorityQueueItem
    {
        return $this->isEmpty() ? null : $this->heap[0];
    }

    /**
     * Extraer múltiples elementos de alta prioridad
     */
    public function extractBatch(int $count): array
    {
        $batch = [];
        $extracted = 0;
        
        while ($extracted < $count && !$this->isEmpty()) {
            $item = $this->extract();
            if ($item !== null) {
                $batch[] = $item;
                $extracted++;
            }
        }
        
        return $batch;
    }

    /**
     * Extraer elementos por umbral de prioridad
     */
    public function extractByThreshold(float $threshold): array
    {
        $items = [];
        
        while (!$this->isEmpty() && $this->comparePriority($this->peek()->priority, $threshold)) {
            $items[] = $this->extract();
        }
        
        return $items;
    }

    /**
     * Actualizar prioridad de un elemento específico
     */
    public function updatePriority(string $id, float $newPriority): bool
    {
        $index = $this->findItemIndex($id);
        
        if ($index === -1) {
            return false;
        }

        $oldPriority = $this->heap[$index]->priority;
        $this->heap[$index]->priority = $newPriority;
        
        // Reorganizar heap según el cambio de prioridad
        if ($this->shouldGoUp($newPriority, $oldPriority)) {
            $this->heapifyUp($index);
        } else {
            $this->heapifyDown($index);
        }
        
        return true;
    }

    /**
     * Buscar elemento por ID
     */
    public function findItem(string $id): ?PriorityQueueItem
    {
        $index = $this->findItemIndex($id);
        return $index !== -1 ? $this->heap[$index] : null;
    }

    /**
     * Remover elemento específico por ID
     */
    public function remove(string $id): bool
    {
        $index = $this->findItemIndex($id);
        
        if ($index === -1) {
            return false;
        }

        // Intercambiar con el último elemento
        $this->heap[$index] = $this->heap[$this->size - 1];
        unset($this->heap[$this->size - 1]);
        $this->size--;
        
        // Reorganizar heap si no era el último elemento
        if ($index < $this->size) {
            $this->heapifyUp($index);
            $this->heapifyDown($index);
        }
        
        $this->stats['current_size'] = $this->size;
        
        return true;
    }

    private function findItemIndex(string $id): int
    {
        for ($i = 0; $i < $this->size; $i++) {
            if ($this->heap[$i]->id === $id) {
                return $i;
            }
        }
        return -1;
    }

    private function heapifyUp(int $index): void
    {
        while ($index > 0) {
            $parentIndex = intval(($index - 1) / 2);
            
            if (!$this->shouldSwap($index, $parentIndex)) {
                break;
            }
            
            $this->swap($index, $parentIndex);
            $index = $parentIndex;
        }
    }

    private function heapifyDown(int $index): void
    {
        while (true) {
            $leftChild = 2 * $index + 1;
            $rightChild = 2 * $index + 2;
            $extremeIndex = $index;
            
            if ($leftChild < $this->size && $this->shouldSwap($leftChild, $extremeIndex)) {
                $extremeIndex = $leftChild;
            }
            
            if ($rightChild < $this->size && $this->shouldSwap($rightChild, $extremeIndex)) {
                $extremeIndex = $rightChild;
            }
            
            if ($extremeIndex === $index) {
                break;
            }
            
            $this->swap($index, $extremeIndex);
            $index = $extremeIndex;
        }
    }

    private function shouldSwap(int $childIndex, int $parentIndex): bool
    {
        $childPriority = $this->heap[$childIndex]->priority;
        $parentPriority = $this->heap[$parentIndex]->priority;
        
        if ($this->isMaxHeap) {
            return $childPriority > $parentPriority;
        } else {
            return $childPriority < $parentPriority;
        }
    }

    private function shouldGoUp(float $newPriority, float $oldPriority): bool
    {
        if ($this->isMaxHeap) {
            return $newPriority > $oldPriority;
        } else {
            return $newPriority < $oldPriority;
        }
    }

    private function comparePriority(float $priority, float $threshold): bool
    {
        if ($this->isMaxHeap) {
            return $priority >= $threshold;
        } else {
            return $priority <= $threshold;
        }
    }

    private function swap(int $i, int $j): void
    {
        $temp = $this->heap[$i];
        $this->heap[$i] = $this->heap[$j];
        $this->heap[$j] = $temp;
    }

    private function updateStats(): void
    {
        $this->stats['total_inserted']++;
        $this->stats['current_size'] = $this->size;
        $this->stats['peak_size'] = max($this->stats['peak_size'], $this->size);
        
        // Calcular estadísticas de prioridad
        if ($this->size > 0) {
            $priorities = array_map(fn($item) => $item->priority, $this->heap);
            $this->stats['average_priority'] = array_sum($priorities) / count($priorities);
            $this->stats['priority_range']['min'] = min($priorities);
            $this->stats['priority_range']['max'] = max($priorities);
        }
    }

    public function isEmpty(): bool
    {
        return $this->size === 0;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function clear(): void
    {
        $this->heap = [];
        $this->size = 0;
        $this->stats['current_size'] = 0;
    }

    public function toArray(): array
    {
        $items = [];
        for ($i = 0; $i < $this->size; $i++) {
            $items[] = $this->heap[$i]->toArray();
        }
        return $items;
    }

    public function getStats(): array
    {
        return array_merge($this->stats, [
            'is_max_heap' => $this->isMaxHeap,
            'heap_type' => $this->isMaxHeap ? 'Max-Heap' : 'Min-Heap',
            'efficiency' => $this->calculateEfficiency()
        ]);
    }

    private function calculateEfficiency(): float
    {
        if ($this->stats['total_inserted'] === 0) return 0;
        return round(($this->stats['total_extracted'] / $this->stats['total_inserted']) * 100, 2);
    }

    /**
     * Validar integridad del heap
     */
    public function validateHeap(): array
    {
        $violations = [];
        
        for ($i = 0; $i < $this->size; $i++) {
            $leftChild = 2 * $i + 1;
            $rightChild = 2 * $i + 2;
            
            if ($leftChild < $this->size) {
                if ($this->shouldSwap($leftChild, $i)) {
                    $violations[] = "Violación en índice $i con hijo izquierdo $leftChild";
                }
            }
            
            if ($rightChild < $this->size) {
                if ($this->shouldSwap($rightChild, $i)) {
                    $violations[] = "Violación en índice $i con hijo derecho $rightChild";
                }
            }
        }
        
        return [
            'is_valid' => empty($violations),
            'violations' => $violations,
            'size' => $this->size
        ];
    }

    /**
     * Factory methods para diferentes tipos de colas
     */
    public static function createForWeatherEvents(): self
    {
        // Max-Heap para procesar eventos meteorológicos por severidad
        return new self(true);
    }

    public static function createForProcessingTasks(): self
    {
        // Max-Heap para tareas por prioridad
        return new self(true);
    }

    public static function createForScheduling(): self
    {
        // Min-Heap para programación por tiempo
        return new self(false);
    }

    /**
     * Métodos auxiliares para casos de uso específicos
     */
    public function insertWeatherEvent(array $weatherData, float $severity): string
    {
        $metadata = [
            'type' => 'weather_event',
            'location' => $weatherData['location'] ?? 'unknown',
            'event_type' => $weatherData['event_type'] ?? 'general',
            'timestamp' => time()
        ];
        
        return $this->insert($weatherData, $severity, null, $metadata);
    }

    public function insertProcessingTask(callable $task, float $priority, string $taskName): string
    {
        $metadata = [
            'type' => 'processing_task',
            'task_name' => $taskName,
            'created_at' => time()
        ];
        
        return $this->insert($task, $priority, null, $metadata);
    }

    public function getHighPriorityItems(float $threshold): array
    {
        $highPriorityItems = [];
        
        // No extraer, solo listar
        for ($i = 0; $i < $this->size; $i++) {
            if ($this->comparePriority($this->heap[$i]->priority, $threshold)) {
                $highPriorityItems[] = $this->heap[$i];
            }
        }
        
        // Ordenar por prioridad
        usort($highPriorityItems, function($a, $b) {
            if ($this->isMaxHeap) {
                return $b->priority <=> $a->priority;
            } else {
                return $a->priority <=> $b->priority;
            }
        });
        
        return $highPriorityItems;
    }
}
