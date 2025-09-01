<?php

namespace Database\Factories;

use App\Models\TechnicalReport;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TechnicalReport>
 */
class TechnicalReportFactory extends Factory
{
    protected $model = TechnicalReport::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement(array_keys(TechnicalReport::TYPES));
        $provider = $this->faker->randomElement(array_keys(TechnicalReport::PROVIDERS));
        $status = $this->faker->randomElement(['completed', 'cached', 'failed']);

        return [
            'title' => $this->generateTitle($type),
            'content' => $this->generateContent($type),
            'summary' => $this->faker->text(200),
            'type' => $type,
            'status' => $status,
            'llm_provider' => $provider,
            'prompt_template' => $this->generatePrompt($type),
            'generation_time' => $this->faker->randomFloat(3, 0.5, 5.0),
            'token_usage' => $this->generateTokenUsage(),
            'data_sources' => $this->generateDataSources($type),
            'metadata' => $this->generateMetadata(),
            'cached_until' => $status === 'cached' ? $this->faker->dateTimeBetween('now', '+24 hours') : null,
            'version' => $this->faker->numberBetween(1, 3),
            'language' => 'es',
            'location_id' => $this->faker->boolean(70) ? Location::factory() : null,
            'user_id' => $this->faker->boolean(80) ? User::factory() : null,
        ];
    }

    /**
     * Estado específico para reportes completados
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'generation_time' => $this->faker->randomFloat(3, 0.8, 3.0),
            'token_usage' => $this->generateTokenUsage(),
            'cached_until' => null,
        ]);
    }

    /**
     * Estado específico para reportes en caché
     */
    public function cached(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cached',
            'cached_until' => $this->faker->dateTimeBetween('now', '+24 hours'),
        ]);
    }

    /**
     * Estado específico para reportes fallidos
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'content' => '',
            'summary' => null,
            'generation_time' => null,
            'token_usage' => null,
            'cached_until' => null,
        ]);
    }

    /**
     * Estado para tipo específico de reporte
     */
    public function ofType(string $type): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => $type,
            'title' => $this->generateTitle($type),
            'content' => $this->generateContent($type),
            'data_sources' => $this->generateDataSources($type),
        ]);
    }

    /**
     * Estado para proveedor específico
     */
    public function withProvider(string $provider): static
    {
        return $this->state(fn (array $attributes) => [
            'llm_provider' => $provider,
            'token_usage' => $provider === 'simulation' ? null : $this->generateTokenUsage(),
        ]);
    }

    /**
     * Estado para reportes recientes
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => $this->faker->dateTimeBetween('-24 hours', 'now'),
        ]);
    }

    /**
     * Genera un título basado en el tipo
     */
    private function generateTitle(string $type): string
    {
        $templates = [
            'general' => [
                'Reporte Técnico General - {date}',
                'Análisis Comprensivo del Sistema - {date}',
                'Evaluación Técnica Integral',
            ],
            'weather' => [
                'Análisis Meteorológico - {location}',
                'Reporte Climático Detallado',
                'Condiciones Atmosféricas - {date}',
            ],
            'spatial' => [
                'Análisis Geoespacial - {location}',
                'Evaluación Territorial Avanzada',
                'Estudio de Características Espaciales',
            ],
            'performance' => [
                'Reporte de Rendimiento del Sistema',
                'Análisis de Performance y Optimización',
                'Evaluación de Métricas Operacionales',
            ],
            'environmental' => [
                'Análisis de Impacto Ambiental - {location}',
                'Evaluación Ecológica Integral',
                'Reporte de Sostenibilidad Ambiental',
            ],
            'predictive' => [
                'Análisis Predictivo Avanzado',
                'Modelado de Tendencias Futuras',
                'Proyecciones y Forecasting - {date}',
            ],
        ];

        $template = $this->faker->randomElement($templates[$type] ?? $templates['general']);
        
        return str_replace(
            ['{date}', '{location}'],
            [
                $this->faker->date('d/m/Y'),
                $this->faker->city(),
            ],
            $template
        );
    }

    /**
     * Genera contenido simulado basado en el tipo
     */
    private function generateContent(string $type): string
    {
        $contents = [
            'general' => $this->generateGeneralContent(),
            'weather' => $this->generateWeatherContent(),
            'spatial' => $this->generateSpatialContent(),
            'performance' => $this->generatePerformanceContent(),
            'environmental' => $this->generateEnvironmentalContent(),
            'predictive' => $this->generatePredictiveContent(),
        ];

        return $contents[$type] ?? $contents['general'];
    }

    private function generateGeneralContent(): string
    {
        return "# Reporte Técnico General

## Resumen Ejecutivo

" . $this->faker->text(300) . "

## Análisis Principal

### Métricas Clave
- Volumen de datos: " . $this->faker->numberBetween(1000, 10000) . " registros
- Tiempo de procesamiento: " . $this->faker->randomFloat(2, 10, 200) . "ms
- Precisión: " . $this->faker->numberBetween(85, 99) . "%

### Indicadores de Rendimiento
" . $this->faker->text(200) . "

## Conclusiones

" . $this->faker->text(150) . "

## Recomendaciones

1. " . $this->faker->sentence() . "
2. " . $this->faker->sentence() . "
3. " . $this->faker->sentence() . "

---
*Reporte generado automáticamente*";
    }

    private function generateWeatherContent(): string
    {
        $temp = $this->faker->numberBetween(10, 35);
        $humidity = $this->faker->numberBetween(30, 90);
        
        return "# Análisis Meteorológico

## Condiciones Actuales

### Temperatura
- **Actual**: {$temp}°C
- **Sensación térmica**: " . ($temp + $this->faker->numberBetween(-3, 3)) . "°C
- **Humedad**: {$humidity}%

### Análisis Atmosférico
" . $this->faker->text(200) . "

## Predicciones

" . $this->faker->text(150) . "

## Recomendaciones Meteorológicas

" . $this->faker->text(100) . "

---
*Análisis meteorológico automatizado*";
    }

    private function generateSpatialContent(): string
    {
        return "# Análisis Geoespacial

## Características del Territorio

### Coordenadas y Ubicación
- **Latitud**: " . $this->faker->latitude() . "
- **Longitud**: " . $this->faker->longitude() . "
- **Elevación**: " . $this->faker->numberBetween(0, 2000) . " msnm

### Análisis Topográfico
" . $this->faker->text(200) . "

## Evaluación Espacial

" . $this->faker->text(150) . "

## Conclusiones Geográficas

" . $this->faker->text(100) . "

---
*Análisis geoespacial avanzado*";
    }

    private function generatePerformanceContent(): string
    {
        return "# Reporte de Rendimiento

## Métricas del Sistema

### CPU y Memoria
- **CPU**: " . $this->faker->numberBetween(10, 80) . "%
- **Memoria**: " . $this->faker->numberBetween(30, 70) . "%
- **Disco**: " . $this->faker->numberBetween(20, 60) . "%

### Red y Conectividad
- **Latencia**: " . $this->faker->numberBetween(5, 50) . "ms
- **Throughput**: " . $this->faker->numberBetween(100, 1000) . " req/s

## Análisis de Performance

" . $this->faker->text(200) . "

## Optimizaciones Sugeridas

" . $this->faker->text(150) . "

---
*Reporte de rendimiento automatizado*";
    }

    private function generateEnvironmentalContent(): string
    {
        return "# Análisis Ambiental

## Indicadores Ecológicos

### Calidad del Aire
- **Índice AQI**: " . $this->faker->numberBetween(25, 150) . "
- **PM2.5**: " . $this->faker->numberBetween(5, 35) . " μg/m³

### Recursos Naturales
" . $this->faker->text(200) . "

## Evaluación de Impacto

" . $this->faker->text(150) . "

## Recomendaciones Ambientales

" . $this->faker->text(100) . "

---
*Análisis ambiental integral*";
    }

    private function generatePredictiveContent(): string
    {
        return "# Análisis Predictivo

## Modelos de Predicción

### Tendencias Identificadas
- **Confianza**: " . $this->faker->numberBetween(75, 95) . "%
- **Horizonte**: " . $this->faker->numberBetween(7, 30) . " días

### Proyecciones
" . $this->faker->text(200) . "

## Análisis de Riesgos

" . $this->faker->text(150) . "

## Estrategias Recomendadas

" . $this->faker->text(100) . "

---
*Análisis predictivo avanzado*";
    }

    /**
     * Genera un prompt simulado
     */
    private function generatePrompt(string $type): string
    {
        return "Genera un reporte técnico de tipo '{$type}' basado en los datos proporcionados. " .
               "El reporte debe incluir análisis detallado, conclusiones y recomendaciones. " .
               "Utiliza formato markdown para mejor legibilidad.";
    }

    /**
     * Genera estadísticas de tokens
     */
    private function generateTokenUsage(): array
    {
        $promptTokens = $this->faker->numberBetween(100, 500);
        $completionTokens = $this->faker->numberBetween(800, 2000);
        
        return [
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
            'total_tokens' => $promptTokens + $completionTokens,
        ];
    }

    /**
     * Genera fuentes de datos simuladas
     */
    private function generateDataSources(string $type): array
    {
        $baseSources = [
            'timestamp' => [
                'type' => 'string',
                'sample' => now()->toISOString(),
                'timestamp' => now()->toISOString(),
            ],
        ];

        switch ($type) {
            case 'weather':
                return array_merge($baseSources, [
                    'weather_data' => [
                        'type' => 'array',
                        'sample' => ['temperature' => 25, 'humidity' => 60],
                        'timestamp' => now()->toISOString(),
                    ],
                ]);

            case 'spatial':
                return array_merge($baseSources, [
                    'coordinates' => [
                        'type' => 'array',
                        'sample' => ['lat' => 40.7128, 'lon' => -74.0060],
                        'timestamp' => now()->toISOString(),
                    ],
                ]);

            case 'performance':
                return array_merge($baseSources, [
                    'system_metrics' => [
                        'type' => 'array',
                        'sample' => ['cpu' => 45, 'memory' => 60],
                        'timestamp' => now()->toISOString(),
                    ],
                ]);

            default:
                return $baseSources;
        }
    }

    /**
     * Genera metadata simulada
     */
    private function generateMetadata(): array
    {
        return [
            'generated_by' => 'factory',
            'version' => '1.0',
            'quality_score' => $this->faker->randomFloat(2, 7.5, 10.0),
            'confidence_level' => $this->faker->randomElement(['high', 'medium', 'low']),
            'data_freshness' => $this->faker->randomElement(['fresh', 'recent', 'stale']),
        ];
    }
}
