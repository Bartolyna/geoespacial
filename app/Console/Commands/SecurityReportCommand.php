<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SecurityReportCommand extends Command
{
    protected $signature = 'security:report {--days=1 : Número de días hacia atrás}';
    protected $description = 'Generar reporte de seguridad basado en logs';

    public function handle()
    {
        $days = $this->option('days');
        $this->info("🔒 Generando reporte de seguridad de los últimos {$days} días...");
        
        $logPath = storage_path('logs/laravel.log');
        
        if (!File::exists($logPath)) {
            $this->error('❌ No se encontró el archivo de logs');
            return 1;
        }
        
        $logContent = File::get($logPath);
        $lines = explode("\n", $logContent);
        
        $since = now()->subDays($days);
        
        $stats = [
            'requests_total' => 0,
            'ips_sospechosas' => [],
            'intentos_xss' => 0,
            'errores_validacion' => 0,
            'locations_creadas' => 0,
        ];
        
        foreach ($lines as $line) {
            if (empty($line)) continue;
            
            // Parsear línea de log
            if (preg_match('/\[(.*?)\]/', $line, $matches)) {
                $timestamp = $matches[1] ?? '';
                
                try {
                    $logTime = \Carbon\Carbon::parse($timestamp);
                    if ($logTime->lt($since)) continue;
                } catch (\Exception $e) {
                    continue;
                }
            }
            
            // Contar requests
            if (str_contains($line, 'Request entrante')) {
                $stats['requests_total']++;
            }
            
            // IPs sospechosas
            if (str_contains($line, 'IP sospechosa detectada')) {
                preg_match('/"ip":"([^"]+)"/', $line, $matches);
                $ip = $matches[1] ?? 'unknown';
                $stats['ips_sospechosas'][$ip] = ($stats['ips_sospechosas'][$ip] ?? 0) + 1;
            }
            
            // Intentos XSS
            if (str_contains($line, 'Intento de XSS detectado')) {
                $stats['intentos_xss']++;
            }
            
            // Errores de validación
            if (str_contains($line, 'Error de validación')) {
                $stats['errores_validacion']++;
            }
            
            // Locations creadas
            if (str_contains($line, 'Ubicación creada exitosamente')) {
                $stats['locations_creadas']++;
            }
        }
        
        $this->displayReport($stats, $days);
        
        return 0;
    }
    
    private function displayReport(array $stats, int $days): void
    {
        $this->newLine();
        $this->line('📊 <fg=cyan>REPORTE DE SEGURIDAD</fg=cyan>');
        $this->line(str_repeat('═', 50));
        
        $this->line("📅 Período: Últimos {$days} días");
        $this->line("🌐 Total de requests: <fg=green>{$stats['requests_total']}</fg=green>");
        $this->line("📍 Ubicaciones creadas: <fg=green>{$stats['locations_creadas']}</fg=green>");
        $this->line("⚠️  Errores de validación: <fg=yellow>{$stats['errores_validacion']}</fg=yellow>");
        $this->line("🚨 Intentos de XSS: <fg=red>{$stats['intentos_xss']}</fg=red>");
        
        if (!empty($stats['ips_sospechosas'])) {
            $this->newLine();
            $this->line('🔍 <fg=red>IPs SOSPECHOSAS:</fg=red>');
            foreach ($stats['ips_sospechosas'] as $ip => $count) {
                $this->line("   • {$ip}: {$count} alertas");
            }
        } else {
            $this->newLine();
            $this->line('✅ <fg=green>No se detectaron IPs sospechosas</fg=green>');
        }
        
        $this->newLine();
        
        if ($stats['intentos_xss'] == 0 && empty($stats['ips_sospechosas'])) {
            $this->line('🛡️  <fg=green>Sistema seguro - Sin amenazas detectadas</fg=green>');
        } else {
            $this->line('⚠️  <fg=yellow>Revisar actividad sospechosa detectada</fg=yellow>');
        }
    }
}
