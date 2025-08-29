<?php

namespace App\Console\Commands;

use App\Services\GeospatialWebSocketService;
use Illuminate\Console\Command;

class CleanupWebSocketConnections extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'websocket:cleanup {--dry-run : Solo mostrar conexiones que serían limpiadas}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpiar conexiones WebSocket inactivas y obsoletas';

    /**
     * Execute the console command.
     */
    public function handle(GeospatialWebSocketService $webSocketService)
    {
        $this->info('🧹 Iniciando limpieza de conexiones WebSocket...');
        
        if ($this->option('dry-run')) {
            $this->warn('🔍 Modo DRY-RUN: Solo mostrar conexiones que serían limpiadas');
        }

        // Obtener estadísticas antes de la limpieza
        $beforeStats = $webSocketService->getConnectionStats();
        $this->info("📊 Conexiones activas antes de limpieza: {$beforeStats['total_connections']}");

        if (!$this->option('dry-run')) {
            // Ejecutar limpieza
            $cleanedConnections = $webSocketService->cleanupStaleConnections();
            
            // Obtener estadísticas después de la limpieza
            $afterStats = $webSocketService->getConnectionStats();
            
            $this->info("✅ Limpieza completada:");
            $this->info("   • Conexiones limpiadas: {$cleanedConnections}");
            $this->info("   • Conexiones restantes: {$afterStats['total_connections']}");
            
            if ($cleanedConnections > 0) {
                $this->warn("⚠️  Se limpiaron {$cleanedConnections} conexiones inactivas");
            } else {
                $this->info("✨ No se encontraron conexiones inactivas para limpiar");
            }
        }

        // Mostrar estadísticas detalladas
        $this->showConnectionStats($webSocketService);
        
        return Command::SUCCESS;
    }

    private function showConnectionStats(GeospatialWebSocketService $webSocketService): void
    {
        $stats = $webSocketService->getConnectionStats();
        
        $this->newLine();
        $this->info('📈 Estadísticas detalladas de conexiones:');
        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Total conexiones', $stats['total_connections']],
                ['Límite máximo', $stats['max_connections']],
                ['Utilización', round(($stats['total_connections'] / $stats['max_connections']) * 100, 2) . '%'],
                ['Conexión más antigua', $stats['oldest_connection'] ? 
                    $stats['oldest_connection']['duration_seconds'] . 's (' . $stats['oldest_connection']['id'] . ')' : 'N/A'],
                ['Conexión más nueva', $stats['newest_connection'] ? 
                    $stats['newest_connection']['duration_seconds'] . 's (' . $stats['newest_connection']['id'] . ')' : 'N/A'],
                ['Edad promedio', $stats['average_connection_age'] . 's'],
            ]
        );

        if (!empty($stats['connections_by_ip'])) {
            $this->newLine();
            $this->info('🌐 Conexiones por IP:');
            $ipData = [];
            foreach ($stats['connections_by_ip'] as $ip => $count) {
                $ipData[] = [$ip, $count];
            }
            $this->table(['IP Address', 'Conexiones'], $ipData);
        }
    }
}
