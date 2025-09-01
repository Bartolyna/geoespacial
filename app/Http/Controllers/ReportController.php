<?php

namespace App\Http\Controllers;

use App\Models\TechnicalReport;
use App\Models\Location;
use App\Services\LLMService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    private LLMService $llmService;

    public function __construct(LLMService $llmService)
    {
        $this->llmService = $llmService;
    }

    /**
     * Muestra la página principal de reportes
     */
    public function index(): View
    {
        $reports = TechnicalReport::with(['location', 'user'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $stats = $this->getStats();

        return view('reports.index', compact('reports', 'stats'));
    }

    /**
     * Muestra el formulario para generar un nuevo reporte
     */
    public function create(): View
    {
        $locations = Location::where('active', true)->get();
        $types = TechnicalReport::TYPES;
        $providers = TechnicalReport::PROVIDERS;

        return view('reports.create', compact('locations', 'types', 'providers'));
    }

    /**
     * Genera un nuevo reporte
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'type' => 'required|string|in:' . implode(',', array_keys(TechnicalReport::TYPES)),
            'location_id' => 'nullable|exists:locations,id',
            'provider' => 'required|string|in:simulation,openai,anthropic',
            'title' => 'nullable|string|max:255',
        ]);

        try {
            $location = $request->location_id ? Location::find($request->location_id) : null;
            
            // Recopilar datos según el tipo de reporte
            $data = $this->gatherDataForReport($request->type, $location);

            // Generar el reporte
            $report = $this->llmService->generateTechnicalReport(
                type: $request->type,
                data: $data,
                location: $location,
                userId: Auth::id(),
                provider: $request->provider,
                options: []
            );

            // Si se proporciona un título personalizado, actualizarlo
            if ($request->title) {
                $report->update(['title' => $request->title]);
            }

            return redirect()->route('reports.show', $report)
                ->with('success', '¡Reporte generado exitosamente!');

        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Error al generar el reporte: ' . $e->getMessage());
        }
    }

    /**
     * Muestra un reporte específico
     */
    public function show(TechnicalReport $report): View
    {
        $report->load(['location', 'user']);
        
        // Buscar reportes relacionados
        $relatedReports = TechnicalReport::where('id', '!=', $report->id)
            ->where(function($query) use ($report) {
                $query->where('type', $report->type)
                      ->orWhere('location_id', $report->location_id);
            })
            ->with(['location', 'user'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
        
        return view('reports.show', compact('report', 'relatedReports'));
    }

    /**
     * Elimina un reporte
     */
    public function destroy(TechnicalReport $report): RedirectResponse
    {
        try {
            $report->delete();
            
            return redirect()->route('reports.index')
                ->with('success', 'Reporte eliminado exitosamente');
                
        } catch (\Exception $e) {
            return back()->with('error', 'Error al eliminar el reporte: ' . $e->getMessage());
        }
    }

    /**
     * Regenera un reporte existente
     */
    public function regenerate(TechnicalReport $report): RedirectResponse
    {
        try {
            $location = $report->location;
            $data = $this->gatherDataForReport($report->type, $location);

            // Generar nueva versión
            $newReport = $this->llmService->generateTechnicalReport(
                type: $report->type,
                data: $data,
                location: $location,
                userId: Auth::id(),
                provider: $report->llm_provider,
                options: $report->metadata ?? []
            );

            return redirect()->route('reports.show', $newReport)
                ->with('success', 'Reporte regenerado exitosamente');

        } catch (\Exception $e) {
            return back()->with('error', 'Error al regenerar el reporte: ' . $e->getMessage());
        }
    }

    /**
     * Exporta un reporte en formato específico
     */
    public function export(TechnicalReport $report, Request $request)
    {
        $format = $request->get('format', 'markdown');
        
        switch ($format) {
            case 'pdf':
                return $this->exportToPdf($report);
                
            case 'json':
                $content = json_encode($report->toArray(), JSON_PRETTY_PRINT);
                $filename = "reporte_{$report->id}_{$report->type}.json";
                $mimeType = 'application/json';
                break;
                
            case 'markdown':
                $content = $report->content;
                $filename = "reporte_{$report->id}_{$report->type}.md";
                $mimeType = 'text/markdown';
                break;
                
            default:
                abort(400, 'Formato no soportado');
        }

        return response($content)
            ->header('Content-Type', $mimeType)
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Descarga un reporte en formato específico
     */
    public function download(TechnicalReport $report, Request $request)
    {
        $format = $request->get('format', 'markdown');
        
        switch ($format) {
            case 'markdown':
                $content = $report->content;
                $filename = "reporte_{$report->id}_{$report->type}.md";
                $mimeType = 'text/markdown';
                break;
                
            case 'html':
                $content = $this->markdownToHtml($report->content);
                $filename = "reporte_{$report->id}_{$report->type}.html";
                $mimeType = 'text/html';
                break;
                
            case 'json':
                $content = json_encode($report->toArray(), JSON_PRETTY_PRINT);
                $filename = "reporte_{$report->id}_{$report->type}.json";
                $mimeType = 'application/json';
                break;
                
            default:
                abort(400, 'Formato no soportado');
        }

        return response($content)
            ->header('Content-Type', $mimeType)
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Obtiene estadísticas para el dashboard
     */
    private function getStats(): array
    {
        $stats = [
            'total_reports' => TechnicalReport::count(),
            'reports_today' => TechnicalReport::whereDate('created_at', today())->count(),
            'reports_by_type' => TechnicalReport::selectRaw('type, COUNT(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type')
                ->toArray(),
            'reports_by_provider' => TechnicalReport::selectRaw('llm_provider, COUNT(*) as count')
                ->groupBy('llm_provider')
                ->pluck('count', 'llm_provider')
                ->toArray(),
            'avg_generation_time' => TechnicalReport::whereNotNull('generation_time')
                ->avg('generation_time'),
            'recent_reports' => TechnicalReport::recent(24)->count(),
        ];

        // Estadísticas del servicio LLM
        $llmStats = $this->llmService->getServiceStats();
        
        return array_merge($stats, $llmStats);
    }

    /**
     * Recopila datos para el reporte según su tipo
     */
    private function gatherDataForReport(string $type, ?Location $location = null): array
    {
        $data = [];

        switch ($type) {
            case 'weather':
                if ($location) {
                    $data['location'] = [
                        'name' => $location->name,
                        'city' => $location->city,
                        'country' => $location->country,
                        'coordinates' => [
                            'latitude' => $location->latitude,
                            'longitude' => $location->longitude,
                        ],
                    ];
                }
                break;

            case 'spatial':
                if ($location) {
                    $data['location'] = [
                        'name' => $location->name,
                        'city' => $location->city,
                        'country' => $location->country,
                        'coordinates' => [
                            'latitude' => $location->latitude,
                            'longitude' => $location->longitude,
                        ],
                    ];
                }
                break;

            case 'performance':
                $data['system'] = [
                    'timestamp' => now()->toISOString(),
                    'environment' => app()->environment(),
                    'php_version' => PHP_VERSION,
                    'laravel_version' => app()->version(),
                ];
                break;

            default:
                $data['general'] = [
                    'timestamp' => now()->toISOString(),
                    'web_generated' => true,
                    'environment' => app()->environment(),
                ];
                
                if ($location) {
                    $data['location'] = [
                        'name' => $location->name,
                        'city' => $location->city,
                        'country' => $location->country,
                    ];
                }
                break;
        }

        return $data;
    }

    /**
     * Exporta un reporte a PDF usando DomPDF
     */
    private function exportToPdf(TechnicalReport $report)
    {
        $pdf = app('dompdf.wrapper');
        $pdf->loadView('reports.pdf', compact('report'));
        
        $filename = "reporte_{$report->id}_{$report->type}.pdf";
        
        return $pdf->download($filename);
    }

    /**
     * Conversión básica de markdown a HTML
     */
    private function markdownToHtml(string $markdown): string
    {
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
