<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class TechnicalReport extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'content',
        'summary',
        'type',
        'status',
        'llm_provider',
        'prompt_template',
        'generation_time',
        'token_usage',
        'data_sources',
        'location_id',
        'user_id',
        'metadata',
        'cached_until',
        'version',
        'language',
    ];

    protected $casts = [
        'data_sources' => 'array',
        'metadata' => 'array',
        'token_usage' => 'array',
        'generation_time' => 'float',
        'cached_until' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'draft',
        'type' => 'general',
        'llm_provider' => 'simulation',
        'language' => 'es',
        'version' => 1,
    ];

    // Tipos de reporte disponibles
    public const TYPES = [
        'general' => 'Reporte General',
        'weather' => 'Análisis Meteorológico',
        'spatial' => 'Análisis Espacial',
        'performance' => 'Rendimiento del Sistema',
        'environmental' => 'Impacto Ambiental',
        'predictive' => 'Análisis Predictivo',
    ];

    // Estados del reporte
    public const STATUSES = [
        'draft' => 'Borrador',
        'generating' => 'Generando',
        'completed' => 'Completado',
        'failed' => 'Fallido',
        'cached' => 'En Caché',
    ];

    // Proveedores LLM
    public const PROVIDERS = [
        'simulation' => 'Simulación',
        'openai' => 'OpenAI GPT',
        'anthropic' => 'Anthropic Claude',
    ];

    /**
     * Relación con la ubicación
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Relación con el usuario
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scopes
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByProvider($query, string $provider)
    {
        return $query->where('llm_provider', $provider);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeCached($query)
    {
        return $query->where('status', 'cached')
                    ->where('cached_until', '>', now());
    }

    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('created_at', '>', now()->subHours($hours));
    }

    /**
     * Accessors & Mutators
     */
    public function getTypeNameAttribute(): string
    {
        return self::TYPES[$this->type] ?? 'Desconocido';
    }

    public function getStatusNameAttribute(): string
    {
        return self::STATUSES[$this->status] ?? 'Desconocido';
    }

    public function getProviderNameAttribute(): string
    {
        return self::PROVIDERS[$this->llm_provider] ?? 'Desconocido';
    }

    public function getIsCachedAttribute(): bool
    {
        return $this->status === 'cached' && 
               $this->cached_until && 
               $this->cached_until->isFuture();
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->cached_until && $this->cached_until->isPast();
    }

    public function getFormattedGenerationTimeAttribute(): string
    {
        if (!$this->generation_time) {
            return 'N/A';
        }

        return $this->generation_time < 1 
            ? round($this->generation_time * 1000) . 'ms'
            : round($this->generation_time, 2) . 's';
    }

    public function getTokenCountAttribute(): int
    {
        if (!$this->token_usage || !is_array($this->token_usage)) {
            return 0;
        }

        return ($this->token_usage['prompt_tokens'] ?? 0) + 
               ($this->token_usage['completion_tokens'] ?? 0);
    }

    /**
     * Convierte el contenido markdown a HTML formateado
     */
    public function getFormattedContentAttribute(): string
    {
        if (!$this->content) {
            return '<p class="text-gray-500 italic">Sin contenido disponible</p>';
        }

        $html = $this->content;
        
        // Headers
        $html = preg_replace('/^# (.+)$/m', '<h1 class="text-2xl font-bold text-gray-900 mb-4 mt-6">$1</h1>', $html);
        $html = preg_replace('/^## (.+)$/m', '<h2 class="text-xl font-semibold text-gray-800 mb-3 mt-5">$1</h2>', $html);
        $html = preg_replace('/^### (.+)$/m', '<h3 class="text-lg font-medium text-gray-700 mb-2 mt-4">$1</h3>', $html);
        
        // Bold and italic
        $html = preg_replace('/\*\*(.+?)\*\*/', '<strong class="font-semibold text-gray-900">$1</strong>', $html);
        $html = preg_replace('/\*(.+?)\*/', '<em class="italic text-gray-700">$1</em>', $html);
        
        // Lists
        $html = preg_replace('/^- (.+)$/m', '<li class="mb-1">$1</li>', $html);
        $html = preg_replace('/^(\d+)\. (.+)$/m', '<li class="mb-1" style="list-style-type: decimal;">$2</li>', $html);
        
        // Wrap consecutive list items in ul/ol
        $html = preg_replace('/(<li class="mb-1">.*?<\/li>)(?=\s*<li class="mb-1">)/s', '$1', $html);
        $html = preg_replace('/(<li class="mb-1">(?:(?!<li|<\/li>).)*<\/li>(?:\s*<li class="mb-1">(?:(?!<li|<\/li>).)*<\/li>)*)/s', 
                           '<ul class="list-disc list-inside mb-4 ml-4">$1</ul>', $html);
        
        // Numbered lists
        $html = preg_replace('/(<li class="mb-1" style="list-style-type: decimal;">(?:(?!<li|<\/li>).)*<\/li>(?:\s*<li class="mb-1" style="list-style-type: decimal;">(?:(?!<li|<\/li>).)*<\/li>)*)/s', 
                           '<ol class="list-decimal list-inside mb-4 ml-4">$1</ol>', $html);
        
        // Paragraphs
        $lines = explode("\n", $html);
        $formatted_lines = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }
            
            // Skip if it's already an HTML element
            if (preg_match('/^<(h[1-6]|ul|ol|li|strong|em)/', $line)) {
                $formatted_lines[] = $line;
            } elseif (!empty($line)) {
                $formatted_lines[] = '<p class="mb-3 text-gray-800 leading-relaxed">' . $line . '</p>';
            }
        }
        
        $html = implode("\n", $formatted_lines);
        
        // Clean up extra spaces and line breaks
        $html = preg_replace('/\s+/', ' ', $html);
        $html = str_replace('> <', '><', $html);
        
        return $html;
    }

    /**
     * Métodos de utilidad
     */
    public function markAsGenerating(): void
    {
        $this->update(['status' => 'generating']);
    }

    public function markAsCompleted(float $generationTime = null, array $tokenUsage = null): void
    {
        $updateData = ['status' => 'completed'];
        
        if ($generationTime !== null) {
            $updateData['generation_time'] = $generationTime;
        }
        
        if ($tokenUsage !== null) {
            $updateData['token_usage'] = $tokenUsage;
        }

        $this->update($updateData);
    }

    public function markAsFailed(): void
    {
        $this->update(['status' => 'failed']);
    }

    public function markAsCached(Carbon $cachedUntil = null): void
    {
        $this->update([
            'status' => 'cached',
            'cached_until' => $cachedUntil ?? now()->addHours((int) config('services.llm.cache_duration', 24))
        ]);
    }

    public function incrementVersion(): void
    {
        $this->increment('version');
    }

    public function addDataSource(string $source, array $data = []): void
    {
        $sources = $this->data_sources ?? [];
        $sources[$source] = $data;
        $this->update(['data_sources' => $sources]);
    }

    public function hasDataSource(string $source): bool
    {
        return isset($this->data_sources[$source]);
    }

    public function getDataSource(string $source): ?array
    {
        return $this->data_sources[$source] ?? null;
    }
}
