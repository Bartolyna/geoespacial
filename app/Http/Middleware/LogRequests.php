<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogRequests
{
    /**
     * Loggear todas las requests entrantes
     */
    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);
        
        // Loggear request entrante
        $this->logIncomingRequest($request);
        
        $response = $next($request);
        
        // Loggear response
        $this->logResponse($request, $response, $startTime);
        
        return $response;
    }
    
    /**
     * Loggear request entrante
     */
    private function logIncomingRequest(Request $request): void
    {
        $ip = $request->ip();
        $userAgent = $request->userAgent();
        $method = $request->method();
        $url = $request->fullUrl();
        
        // Datos sensibles a loggear sin valores
        $inputKeys = array_keys($request->except(['password', 'token', 'api_key']));
        
        Log::info('Request entrante', [
            'ip' => $ip,
            'method' => $method,
            'url' => $url,
            'user_agent' => $userAgent,
            'input_fields' => $inputKeys,
            'timestamp' => now()->toISOString(),
        ]);
        
        // Detectar IPs sospechosas (muchas requests en poco tiempo)
        $this->detectSuspiciousActivity($ip);
    }
    
    /**
     * Loggear response
     */
    private function logResponse(Request $request, $response, float $startTime): void
    {
        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2); // ms
        
        Log::info('Response enviada', [
            'ip' => $request->ip(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'status_code' => $response->getStatusCode(),
            'duration_ms' => $duration,
            'timestamp' => now()->toISOString(),
        ]);
    }
    
    /**
     * Detectar actividad sospechosa
     */
    private function detectSuspiciousActivity(string $ip): void
    {
        $cacheKey = "requests_count_{$ip}";
        $currentCount = cache()->get($cacheKey, 0);
        $newCount = $currentCount + 1;
        
        // Si más de 100 requests en 1 minuto, marcar como sospechoso
        if ($newCount > 100) {
            Log::warning('IP sospechosa detectada', [
                'ip' => $ip,
                'requests_count' => $newCount,
                'timeframe' => '1 minuto',
                'timestamp' => now()->toISOString(),
            ]);
        }
        
        // Guardar contador por 1 minuto
        cache()->put($cacheKey, $newCount, 60);
    }
}
