<?php

namespace App\Console\Commands;

use App\Services\OpenWeatherService;
use App\Services\GeospatialWebSocketService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GeospatialRealtimeCommand extends Command
{
    protected $signature = 'geospatial:realtime 
                            {--interval=60 : Intervalo de actualización en segundos}
                            {--once : Ejecutar solo una vez}';

    protected $description = 'Servicio en tiempo real para captura de datos geoespaciales de OpenWeather';

    private GeospatialWebSocketService $webSocketService;

    public function __construct(
        OpenWeatherService $weatherService,
        GeospatialWebSocketService $webSocketService
    ) {
        parent::__construct();
        $this->webSocketService = $webSocketService;
    }

    public function handle(): int
    {
        $interval = (int) $this->option('interval');
        $runOnce = $this->option('once');

        $this->info('🌍 Iniciando servicio geoespacial en tiempo real');
        $this->info("📊 Intervalo de actualización: {$interval} segundos");
        
        if ($runOnce) {
            $this->info('🔄 Modo: Ejecución única');
            return $this->runOnce();
        }

        $this->info('🔄 Modo: Monitoreo continuo');
        $this->info('⏹️  Presiona Ctrl+C para detener');

        return $this->runContinuous($interval);
    }

    private function runOnce(): int
    {
        try {
            $this->line('Actualizando datos meteorológicos...');
            $this->webSocketService->updateAllLocations();
            
            $this->line('Enviando resumen WebSocket...');
            $this->webSocketService->broadcastSummary();
            
            $stats = $this->webSocketService->getServiceStats();
            $this->displayStats($stats);
            
            $this->info('✅ Actualización completada exitosamente');
            return self::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("❌ Error: {$e->getMessage()}");
            Log::error('Error en comando geospatial:realtime', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return self::FAILURE;
        }
    }

    private function runContinuous(int $interval): int
    {
        $iteration = 0;
        
        while (true) {
            try {
                $iteration++;
                $startTime = microtime(true);
                
                $this->line("📡 Iteración #{$iteration} - " . now()->format('Y-m-d H:i:s'));
                
                // Actualizar todas las ubicaciones
                $this->webSocketService->updateAllLocations();
                
                // Enviar resumen cada 5 iteraciones
                if ($iteration % 5 === 0) {
                    $this->line('📤 Enviando resumen WebSocket...');
                    $this->webSocketService->broadcastSummary();
                }
                
                $endTime = microtime(true);
                $executionTime = round($endTime - $startTime, 2);
                
                $this->line("⏱️  Tiempo de ejecución: {$executionTime}s");
                
                // Mostrar estadísticas cada 10 iteraciones
                if ($iteration % 10 === 0) {
                    $stats = $this->webSocketService->getServiceStats();
                    $this->displayStats($stats);
                }
                
                $this->line("⏳ Esperando {$interval} segundos...\n");
                sleep($interval);
                
            } catch (\Exception $e) {
                $this->error("❌ Error en iteración #{$iteration}: {$e->getMessage()}");
                Log::error('Error en monitoreo continuo', [
                    'iteration' => $iteration,
                    'error' => $e->getMessage()
                ]);
                
                // Esperar antes de reintentar
                $this->line('⏳ Esperando 30 segundos antes de reintentar...');
                sleep(30);
            }
        }

        return self::SUCCESS;
    }

    private function displayStats(array $stats): void
    {
        $this->newLine();
        $this->line('📊 <fg=cyan>Estadísticas del Servicio</fg=cyan>');
        $this->line("├─ Ubicaciones activas: <fg=green>{$stats['active_locations']}</fg=green>");
        $this->line("├─ Total registros: <fg=blue>{$stats['total_weather_records']}</fg=blue>");
        $this->line("├─ Registros última hora: <fg=yellow>{$stats['recent_records_1h']}</fg=yellow>");
        $this->line("├─ Estado: <fg=green>{$stats['service_status']}</fg=green>");
        $this->line("└─ Última actualización: {$stats['last_update']->format('Y-m-d H:i:s')}");
        $this->newLine();
    }
}
