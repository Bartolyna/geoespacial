<?php

namespace App\Console\Commands;

use App\Models\Location;
use App\Models\TechnicalReport;
use App\Services\LLMService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GenerateTechnicalReportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:generate 
                            {type : Tipo de reporte (general, weather, spatial, performance, environmental, predictive)}
                            {--location= : ID de la ubicación (opcional)}
                            {--provider= : Proveedor LLM (simulation, openai, anthropic)}
                            {--title= : Título personalizado del reporte}
                            {--no-cache : Desactivar caché para este reporte}
                            {--export= : Exportar a formato específico (json, markdown, html)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Genera un reporte técnico utilizando LLM';

    private LLMService $llmService;

    public function __construct(LLMService $llmService)
    {
        parent::__construct();
        $this->llmService = $llmService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $type = $this->argument('type');
        $locationId = $this->option('location');
        $provider = $this->option('provider') ?? 'simulation';
        $title = $this->option('title');
        $noCache = $this->option('no-cache');
        $export = $this->option('export');

        // Validar tipo de reporte
        if (!array_key_exists($type, TechnicalReport::TYPES)) {
            $this->error("Tipo de reporte inválido. Tipos disponibles: " . implode(', ', array_keys(TechnicalReport::TYPES)));
            return 1;
        }

        // Validar proveedor
        if (!array_key_exists($provider, TechnicalReport::PROVIDERS)) {
            $this->error("Proveedor inválido. Proveedores disponibles: " . implode(', ', array_keys(TechnicalReport::PROVIDERS)));
            return 1;
        }

        // Obtener ubicación si se especifica
        $location = null;
        if ($locationId) {
            $location = Location::find($locationId);
            if (!$location) {
                $this->error("Ubicación con ID {$locationId} no encontrada.");
                return 1;
            }
        }

        $this->info("Generando reporte técnico...");
        $this->info("Tipo: " . TechnicalReport::TYPES[$type]);
        $this->info("Proveedor: " . TechnicalReport::PROVIDERS[$provider]);
        
        if ($location) {
            $this->info("Ubicación: {$location->name} ({$location->country})");
        }

        // Mostrar barra de progreso
        $progressBar = $this->output->createProgressBar(3);
        $progressBar->start();

        try {
            // Paso 1: Recopilar datos
            $progressBar->setMessage('Recopilando datos...');
            $progressBar->advance();

            $data = $this->gatherDataForReport($type, $location);

            // Paso 2: Configurar opciones
            $progressBar->setMessage('Configurando generación...');
            $progressBar->advance();

            $options = [];
            if ($noCache) {
                $options['disable_cache'] = true;
            }
            if ($title) {
                $options['custom_title'] = $title;
            }

            // Paso 3: Generar reporte
            $progressBar->setMessage('Generando reporte...');
            $progressBar->advance();

            $report = $this->llmService->generateTechnicalReport(
                type: $type,
                data: $data,
                location: $location,
                userId: null, // Comando ejecutado por sistema
                provider: $provider,
                options: $options
            );

            $progressBar->finish();
            $this->newLine(2);

            // Aplicar título personalizado si se especifica
            if ($title) {
                $report->update(['title' => $title]);
            }

            // Mostrar información del reporte generado
            $this->info("✅ Reporte generado exitosamente!");
            $this->table(
                ['Campo', 'Valor'],
                [
                    ['ID', $report->id],
                    ['Título', $report->title],
                    ['Tipo', $report->type_name],
                    ['Proveedor', $report->provider_name],
                    ['Estado', $report->status_name],
                    ['Tiempo de generación', $report->formatted_generation_time],
                    ['Tokens utilizados', $report->token_count],
                    ['Creado', $report->created_at->format('d/m/Y H:i:s')],
                ]
            );

            // Exportar si se solicita
            if ($export) {
                $this->exportReport($report, $export);
            }

            // Mostrar resumen del contenido
            if ($this->confirm('¿Deseas ver un resumen del contenido?', false)) {
                $this->info("\n📄 Resumen del reporte:");
                $this->line($report->summary ?: 'Sin resumen disponible');
            }

            // Mostrar contenido completo si se solicita
            if ($this->confirm('¿Deseas ver el contenido completo?', false)) {
                $this->info("\n📋 Contenido completo:");
                $this->line($report->content);
            }

            return 0;

        } catch (\Exception $e) {
            $progressBar->finish();
            $this->newLine();
            $this->error("❌ Error al generar el reporte: " . $e->getMessage());
            
            Log::error('Error en comando generate-report', [
                'type' => $type,
                'provider' => $provider,
                'location_id' => $locationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return 1;
        }
    }

    /**
     * Recopila datos para el reporte según su tipo
     */
    private function gatherDataForReport(string $type, ?Location $location): array
    {
        $data = [];

        switch ($type) {
            case 'weather':
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
                $data['timestamp'] = now()->toISOString();
                break;

            case 'spatial':
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
                    'command_executed' => true,
                    'environment' => app()->environment(),
                ];
                
                if ($location) {
                    $data['location'] = [
                        'name' => $location->name,
                        'country' => $location->country,
                    ];
                }
                break;
        }

        return $data;
    }

    /**
     * Exporta el reporte al formato especificado
     */
    private function exportReport(TechnicalReport $report, string $format): void
    {
        $filename = "report_{$report->id}_{$report->type}_" . now()->format('Y-m-d_H-i-s');

        try {
            switch ($format) {
                case 'json':
                    $content = json_encode($report->toArray(), JSON_PRETTY_PRINT);
                    $filename .= '.json';
                    break;

                case 'markdown':
                    $content = $report->content;
                    $filename .= '.md';
                    break;

                case 'html':
                    $content = $this->markdownToHtml($report->content);
                    $filename .= '.html';
                    break;

                default:
                    $this->warn("Formato de exportación no soportado: {$format}");
                    return;
            }

            $path = storage_path("app/reports/{$filename}");
            
            // Crear directorio si no existe
            if (!is_dir(dirname($path))) {
                mkdir(dirname($path), 0755, true);
            }

            file_put_contents($path, $content);
            
            $this->info("📁 Reporte exportado a: {$path}");

        } catch (\Exception $e) {
            $this->error("Error al exportar el reporte: " . $e->getMessage());
        }
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
