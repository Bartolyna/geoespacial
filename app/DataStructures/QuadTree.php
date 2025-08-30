<?php

namespace App\DataStructures;

/**
 * Implementación de Quad-Tree para optimización espacial
 * Permite búsquedas eficientes por región y radio
 */
class QuadTreeNode
{
    public float $x;
    public float $y;
    public float $width;
    public float $height;
    public array $points = [];
    public ?QuadTreeNode $topLeft = null;
    public ?QuadTreeNode $topRight = null;
    public ?QuadTreeNode $bottomLeft = null;
    public ?QuadTreeNode $bottomRight = null;
    public bool $divided = false;
    public int $capacity;

    public function __construct(float $x, float $y, float $width, float $height, int $capacity = 4)
    {
        $this->x = $x;
        $this->y = $y;
        $this->width = $width;
        $this->height = $height;
        $this->capacity = $capacity;
    }

    public function contains(QuadTreePoint $point): bool
    {
        return $point->x >= $this->x && $point->x < $this->x + $this->width &&
               $point->y >= $this->y && $point->y < $this->y + $this->height;
    }

    public function subdivide(): void
    {
        $x = $this->x;
        $y = $this->y;
        $w = $this->width / 2;
        $h = $this->height / 2;

        $this->topLeft = new QuadTreeNode($x, $y, $w, $h, $this->capacity);
        $this->topRight = new QuadTreeNode($x + $w, $y, $w, $h, $this->capacity);
        $this->bottomLeft = new QuadTreeNode($x, $y + $h, $w, $h, $this->capacity);
        $this->bottomRight = new QuadTreeNode($x + $w, $y + $h, $w, $h, $this->capacity);

        $this->divided = true;
    }

    public function insert(QuadTreePoint $point): bool
    {
        if (!$this->contains($point)) {
            return false;
        }

        if (count($this->points) < $this->capacity && !$this->divided) {
            $this->points[] = $point;
            return true;
        }

        if (!$this->divided) {
            $this->subdivide();
            
            // Redistribuir puntos existentes
            foreach ($this->points as $existingPoint) {
                $this->insertIntoQuadrant($existingPoint);
            }
            $this->points = [];
        }

        return $this->insertIntoQuadrant($point);
    }

    private function insertIntoQuadrant(QuadTreePoint $point): bool
    {
        return $this->topLeft->insert($point) ||
               $this->topRight->insert($point) ||
               $this->bottomLeft->insert($point) ||
               $this->bottomRight->insert($point);
    }

    public function query(float $x, float $y, float $width, float $height): array
    {
        $found = [];
        $range = new QuadTreeRange($x, $y, $width, $height);

        if (!$this->intersects($range)) {
            return $found;
        }

        // Verificar puntos en este nodo
        foreach ($this->points as $point) {
            if ($range->contains($point)) {
                $found[] = $point;
            }
        }

        // Buscar recursivamente en cuadrantes
        if ($this->divided) {
            $found = array_merge($found, $this->topLeft->query($x, $y, $width, $height));
            $found = array_merge($found, $this->topRight->query($x, $y, $width, $height));
            $found = array_merge($found, $this->bottomLeft->query($x, $y, $width, $height));
            $found = array_merge($found, $this->bottomRight->query($x, $y, $width, $height));
        }

        return $found;
    }

    public function queryRadius(float $centerX, float $centerY, float $radius): array
    {
        $found = [];
        
        // Crear un cuadrado que contenga el círculo
        $x = $centerX - $radius;
        $y = $centerY - $radius;
        $width = $radius * 2;
        $height = $radius * 2;
        
        $candidates = $this->query($x, $y, $width, $height);
        
        // Filtrar por distancia real (círculo)
        foreach ($candidates as $point) {
            $distance = sqrt(pow($point->x - $centerX, 2) + pow($point->y - $centerY, 2));
            if ($distance <= $radius) {
                $point->distance = $distance;
                $found[] = $point;
            }
        }
        
        // Ordenar por distancia
        usort($found, fn($a, $b) => $a->distance <=> $b->distance);
        
        return $found;
    }

    private function intersects(QuadTreeRange $range): bool
    {
        return !($range->x > $this->x + $this->width ||
                $range->x + $range->width < $this->x ||
                $range->y > $this->y + $this->height ||
                $range->y + $range->height < $this->y);
    }

    public function getStats(): array
    {
        $stats = [
            'total_nodes' => 1,
            'leaf_nodes' => $this->divided ? 0 : 1,
            'total_points' => count($this->points),
            'max_depth' => 0,
            'memory_usage' => 0
        ];

        if ($this->divided) {
            $subStats = [
                $this->topLeft->getStats(),
                $this->topRight->getStats(),
                $this->bottomLeft->getStats(),
                $this->bottomRight->getStats()
            ];

            foreach ($subStats as $subStat) {
                $stats['total_nodes'] += $subStat['total_nodes'];
                $stats['leaf_nodes'] += $subStat['leaf_nodes'];
                $stats['total_points'] += $subStat['total_points'];
                $stats['max_depth'] = max($stats['max_depth'], $subStat['max_depth']);
                $stats['memory_usage'] += $subStat['memory_usage'];
            }
            $stats['max_depth']++;
        }

        $stats['memory_usage'] += memory_get_usage();
        $stats['average_points_per_leaf'] = $stats['leaf_nodes'] > 0 
            ? round($stats['total_points'] / $stats['leaf_nodes'], 2) : 0;

        return $stats;
    }
}

class QuadTreePoint
{
    public float $x;
    public float $y;
    public mixed $data;
    public float $distance = 0; // Para cálculos de distancia

    public function __construct(float $x, float $y, mixed $data = null)
    {
        $this->x = $x;
        $this->y = $y;
        $this->data = $data;
    }

    public function toArray(): array
    {
        return [
            'x' => $this->x,
            'y' => $this->y,
            'data' => $this->data,
            'distance' => $this->distance
        ];
    }
}

class QuadTreeRange
{
    public float $x;
    public float $y;
    public float $width;
    public float $height;

    public function __construct(float $x, float $y, float $width, float $height)
    {
        $this->x = $x;
        $this->y = $y;
        $this->width = $width;
        $this->height = $height;
    }

    public function contains(QuadTreePoint $point): bool
    {
        return $point->x >= $this->x && $point->x < $this->x + $this->width &&
               $point->y >= $this->y && $point->y < $this->y + $this->height;
    }
}

class QuadTree
{
    private QuadTreeNode $root;
    private int $totalPoints = 0;
    private array $bounds;

    public function __construct(float $x, float $y, float $width, float $height, int $capacity = 4)
    {
        $this->root = new QuadTreeNode($x, $y, $width, $height, $capacity);
        $this->bounds = ['x' => $x, 'y' => $y, 'width' => $width, 'height' => $height];
    }

    public function insert(float $x, float $y, mixed $data = null): bool
    {
        $point = new QuadTreePoint($x, $y, $data);
        if ($this->root->insert($point)) {
            $this->totalPoints++;
            return true;
        }
        return false;
    }

    public function query(float $x, float $y, float $width, float $height): array
    {
        return $this->root->query($x, $y, $width, $height);
    }

    public function queryRadius(float $centerX, float $centerY, float $radius): array
    {
        return $this->root->queryRadius($centerX, $centerY, $radius);
    }

    public function getTotalPoints(): int
    {
        return $this->totalPoints;
    }

    public function getBounds(): array
    {
        return $this->bounds;
    }

    public function getStats(): array
    {
        $stats = $this->root->getStats();
        $stats['bounds'] = $this->bounds;
        $stats['efficiency'] = $this->calculateEfficiency();
        return $stats;
    }

    private function calculateEfficiency(): float
    {
        $stats = $this->root->getStats();
        if ($stats['total_nodes'] === 0) return 0;
        
        // Eficiencia basada en distribución de puntos
        return round(($stats['total_points'] / $stats['total_nodes']) * 100, 2);
    }

    public static function createFromLocations(array $locations, int $capacity = 4): self
    {
        if (empty($locations)) {
            return new self(-180, -90, 360, 180, $capacity);
        }

        // Calcular bounds automáticamente
        $minLat = $maxLat = $locations[0]['latitude'] ?? 0;
        $minLng = $maxLng = $locations[0]['longitude'] ?? 0;
        
        foreach ($locations as $location) {
            $lat = $location['latitude'] ?? 0;
            $lng = $location['longitude'] ?? 0;
            
            $minLat = min($minLat, $lat);
            $maxLat = max($maxLat, $lat);
            $minLng = min($minLng, $lng);
            $maxLng = max($maxLng, $lng);
        }
        
        // Agregar padding del 10%
        $latRange = $maxLat - $minLat;
        $lngRange = $maxLng - $minLng;
        $padding = 0.1;
        
        if ($latRange > 0) {
            $minLat -= $latRange * $padding;
            $maxLat += $latRange * $padding;
        } else {
            $minLat -= 1;
            $maxLat += 1;
        }
        
        if ($lngRange > 0) {
            $minLng -= $lngRange * $padding;
            $maxLng += $lngRange * $padding;
        } else {
            $minLng -= 1;
            $maxLng += 1;
        }
        
        $quadTree = new self($minLng, $minLat, $maxLng - $minLng, $maxLat - $minLat, $capacity);
        
        // Insertar todas las ubicaciones
        foreach ($locations as $location) {
            $quadTree->insert(
                $location['longitude'] ?? 0,
                $location['latitude'] ?? 0,
                $location
            );
        }
        
        return $quadTree;
    }
}
