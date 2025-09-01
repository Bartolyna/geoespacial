<?php

namespace App\Http\Controllers;

use App\Models\TechnicalReport;
use App\Models\Location;
use App\Services\LLMService;
use App\Services\OpenWeatherService;
use App\Services\PostGISService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Exception;

class TechnicalReportController extends Controller
{
    private LLMService $llmService;
    private OpenWeatherService $weatherService;
    private PostGISService $postgisService;

    public function __construct(
        LLMService $llmService,
        OpenWeatherService $weatherService,
        PostGISService $postgisService
    ) {
        $this->llmService = $llmService;
        $this->weatherService = $weatherService;
        $this->postgisService = $postgisService;
    }

    /**
     * Lista todos los reportes técnicos
     */
    public function index(Request $request): JsonResponse
    {
        $query = TechnicalReport::with(['location', 'user'])
            ->orderBy('created_at', 'desc');

        // Filtros
        if ($request->has('type') && $request->type) {
            $query->byType($request->type);
        }

        if ($request->has('provider') && $request->provider) {
            $query->byProvider($request->provider);
        }

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('location_id') && $request->location_id) {
            $query->where('location_id', $request->location_id);
        }

        // Paginación
        $perPage = min($request->get('per_page', 15), 100);
        $reports = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $reports->items(),
            'pagination' => [
                'current_page' => $reports->currentPage(),
                'last_page' => $reports->lastPage(),
                'per_page' => $reports->perPage(),
                'total' => $reports->total(),
            ],
        ]);
    }

    /**
     * Muestra un reporte técnico específico
     */
    public function show(TechnicalReport $report): JsonResponse
    {
        $report->load(['location', 'user']);

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    /**
     * Genera un nuevo reporte técnico
     */
    public function generate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|string|in:' . implode(',', array_keys(TechnicalReport::TYPES)),
            'location_id' => 'nullable|exists:locations,id',
            'provider' => 'nullable|string|in:simulation,openai,anthropic',
            'data_sources' => 'nullable|array',
            'options' => 'nullable|array',
            'title' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de entrada inválidos',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $location = null;
            if ($request->location_id) {
                $location = Location::findOrFail($request->location_id);
            }

            // Recopilar datos según el tipo de reporte
            $data = $this->gatherDataForReport(
                $request->type,
                $location,
                $request->data_sources ?? []
            );

            // Generar el reporte
            $report = $this->llmService->generateTechnicalReport(
                type: $request->type,
                data: $data,
                location: $location,
                userId: Auth::id(),
                provider: $request->provider,
                options: $request->options ?? []
            );

            // Si se proporciona un título personalizado, actualizarlo
            if ($request->title) {
                $report->update(['title' => $request->title]);
            }

            $report->load(['location', 'user']);

            return response()->json([
                'success' => true,
                'message' => 'Reporte técnico generado exitosamente',
                'data' => $report,
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar el reporte técnico: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Regenera un reporte existente
     */
    public function regenerate(TechnicalReport $report, Request $request): JsonResponse
    {
        try {
            // Marcar el reporte actual como versión anterior
            $report->incrementVersion();

            $location = $report->location;
            $data = $this->gatherDataForReport($report->type, $location);

            // Generar nueva versión
            $newReport = $this->llmService->generateTechnicalReport(
                type: $report->type,
                data: $data,
                location: $location,
                userId: Auth::id(),
                provider: $request->provider ?? $report->llm_provider,
                options: $request->options ?? $report->metadata ?? []
            );

            $newReport->load(['location', 'user']);

            return response()->json([
                'success' => true,
                'message' => 'Reporte regenerado exitosamente',
                'data' => $newReport,
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al regenerar el reporte: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Elimina un reporte técnico
     */
    public function destroy(TechnicalReport $report): JsonResponse
    {
        try {
            $report->delete();

            return response()->json([
                'success' => true,
                'message' => 'Reporte eliminado exitosamente',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el reporte: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtiene estadísticas de los reportes
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = [
                'total_reports' => TechnicalReport::count(),
                'reports_by_type' => TechnicalReport::selectRaw('type, COUNT(*) as count')
                    ->groupBy('type')
                    ->pluck('count', 'type'),
                'reports_by_provider' => TechnicalReport::selectRaw('llm_provider, COUNT(*) as count')
                    ->groupBy('llm_provider')
                    ->pluck('count', 'llm_provider'),
                'reports_by_status' => TechnicalReport::selectRaw('status, COUNT(*) as count')
                    ->groupBy('status')
                    ->pluck('count', 'status'),
                'recent_reports' => TechnicalReport::recent(24)->count(),
                'avg_generation_time' => TechnicalReport::whereNotNull('generation_time')
                    ->avg('generation_time'),
                'cache_hit_rate' => $this->calculateCacheHitRate(),
            ];

            // Estadísticas del servicio LLM
            $llmStats = $this->llmService->getServiceStats();

            return response()->json([
                'success' => true,
                'data' => array_merge($stats, $llmStats),
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtiene los tipos de reporte disponibles
     */
    public function types(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'types' => TechnicalReport::TYPES,
                'providers' => TechnicalReport::PROVIDERS,
                'statuses' => TechnicalReport::STATUSES,
            ],
        ]);
    }

    /**
     * Exporta un reporte en formato específico
     */
    public function export(TechnicalReport $report, Request $request): JsonResponse
    {
        $format = $request->get('format', 'json');

        try {
            switch ($format) {
                case 'markdown':
                    return response()->json([
                        'success' => true,
                        'data' => [
                            'content' => $report->content,
                            'format' => 'markdown',
                        ],
                    ]);

                case 'html':
                    // Convertir markdown a HTML si es necesario
                    $html = $this->markdownToHtml($report->content);
                    return response()->json([
                        'success' => true,
                        'data' => [
                            'content' => $html,
                            'format' => 'html',
                        ],
                    ]);

                case 'pdf':
                    // Aquí podrías integrar una librería para generar PDF
                    return response()->json([
                        'success' => false,
                        'message' => 'Exportación a PDF no implementada aún',
                    ], 501);

                default:
                    return response()->json([
                        'success' => true,
                        'data' => $report,
                    ]);
            }

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al exportar el reporte: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Recopila datos para el reporte según su tipo
     */
    private function gatherDataForReport(string $type, ?Location $location = null, array $additionalSources = []): array
    {
        $data = [];

        try {
            switch ($type) {
                case 'weather':
                    if ($location) {
                        $weatherData = $this->weatherService->getCurrentWeather($location);
                        $data['weather'] = $weatherData;
                        $data['weather_summary'] = $this->weatherService->getWeatherSummary($location);
                    }
                    break;

                case 'spatial':
                    if ($location) {
                        $data['spatial'] = [
                            'coordinates' => [
                                'latitude' => $location->latitude,
                                'longitude' => $location->longitude,
                            ],
                            'country' => $location->country,
                            'nearby_locations' => $this->postgisService->findNearbyLocations($location, 50),
                        ];
                    }
                    break;

                case 'performance':
                    $data['performance'] = [
                        'system_metrics' => $this->getSystemMetrics(),
                        'database_stats' => $this->getDatabaseStats(),
                        'api_performance' => $this->getApiPerformanceStats(),
                    ];
                    break;

                case 'environmental':
                    if ($location) {
                        $data['environmental'] = [
                            'air_quality' => $this->getAirQualityData($location),
                            'water_resources' => $this->getWaterResourcesData($location),
                            'biodiversity' => $this->getBiodiversityData($location),
                        ];
                    }
                    break;

                case 'predictive':
                    $data['predictive'] = [
                        'historical_trends' => $this->getHistoricalTrends($location),
                        'forecast_models' => $this->getForecastModels($location),
                        'risk_assessment' => $this->getRiskAssessment($location),
                    ];
                    break;

                default: // general
                    $data['general'] = [
                        'system_status' => 'operational',
                        'data_quality' => 'high',
                        'last_updated' => now()->toISOString(),
                    ];
                    
                    if ($location) {
                        $data['location'] = [
                            'name' => $location->name,
                            'country' => $location->country,
                            'coordinates' => [
                                'latitude' => $location->latitude,
                                'longitude' => $location->longitude,
                            ],
                        ];
                    }
                    break;
            }

            // Agregar fuentes de datos adicionales
            foreach ($additionalSources as $source => $sourceData) {
                $data[$source] = $sourceData;
            }

            return $data;

        } catch (Exception $e) {
            // En caso de error, devolver datos básicos
            return [
                'error' => 'Error al recopilar datos: ' . $e->getMessage(),
                'timestamp' => now()->toISOString(),
                'fallback_mode' => true,
            ];
        }
    }

    /**
     * Métodos de utilidad para obtener datos simulados
     */
    private function getSystemMetrics(): array
    {
        return [
            'cpu_usage' => rand(10, 80),
            'memory_usage' => rand(30, 70),
            'disk_usage' => rand(20, 60),
            'network_latency' => rand(5, 50),
            'uptime' => rand(24, 720) . ' hours',
        ];
    }

    private function getDatabaseStats(): array
    {
        return [
            'connections' => rand(10, 100),
            'query_time_avg' => rand(50, 200) . 'ms',
            'cache_hit_rate' => rand(80, 98) . '%',
            'storage_used' => rand(30, 80) . '%',
        ];
    }

    private function getApiPerformanceStats(): array
    {
        return [
            'requests_per_minute' => rand(50, 500),
            'response_time_avg' => rand(100, 800) . 'ms',
            'error_rate' => rand(0, 5) . '%',
            'throughput' => rand(100, 1000) . ' req/sec',
        ];
    }

    private function getAirQualityData(Location $location): array
    {
        return [
            'aqi' => rand(25, 150),
            'pm25' => rand(5, 35),
            'pm10' => rand(10, 50),
            'ozone' => rand(20, 100),
            'status' => ['Good', 'Moderate', 'Unhealthy'][rand(0, 2)],
        ];
    }

    private function getWaterResourcesData(Location $location): array
    {
        return [
            'quality_index' => rand(60, 95),
            'availability' => rand(70, 100) . '%',
            'consumption_rate' => rand(100, 300) . 'L/day',
            'contamination_level' => ['Low', 'Medium', 'High'][rand(0, 2)],
        ];
    }

    private function getBiodiversityData(Location $location): array
    {
        return [
            'species_count' => rand(50, 300),
            'endangered_species' => rand(2, 15),
            'forest_coverage' => rand(30, 80) . '%',
            'habitat_quality' => ['Poor', 'Fair', 'Good', 'Excellent'][rand(0, 3)],
        ];
    }

    private function getHistoricalTrends(Location $location): array
    {
        return [
            'data_points' => rand(100, 1000),
            'trend_direction' => ['Increasing', 'Stable', 'Decreasing'][rand(0, 2)],
            'seasonal_patterns' => rand(2, 4),
            'anomalies_detected' => rand(0, 5),
        ];
    }

    private function getForecastModels(Location $location): array
    {
        return [
            'model_accuracy' => rand(75, 95) . '%',
            'confidence_interval' => rand(85, 98) . '%',
            'prediction_horizon' => rand(7, 30) . ' days',
            'variables_considered' => rand(5, 15),
        ];
    }

    private function getRiskAssessment(Location $location): array
    {
        return [
            'overall_risk' => ['Low', 'Medium', 'High'][rand(0, 2)],
            'climate_risk' => rand(1, 10),
            'environmental_risk' => rand(1, 10),
            'infrastructure_risk' => rand(1, 10),
            'mitigation_score' => rand(60, 90),
        ];
    }

    private function calculateCacheHitRate(): float
    {
        $totalReports = TechnicalReport::count();
        $cachedReports = TechnicalReport::where('status', 'cached')->count();
        
        return $totalReports > 0 ? round(($cachedReports / $totalReports) * 100, 2) : 0;
    }

    private function markdownToHtml(string $markdown): string
    {
        // Conversión básica de markdown a HTML
        // En un proyecto real, usarías una librería como league/commonmark
        $html = $markdown;
        
        // Headers
        $html = preg_replace('/^# (.+)$/m', '<h1>$1</h1>', $html);
        $html = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $html);
        $html = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $html);
        
        // Bold and italic
        $html = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $html);
        $html = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $html);
        
        // Line breaks
        $html = nl2br($html);
        
        return $html;
    }
}
