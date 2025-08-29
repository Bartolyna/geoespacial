<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SanitizeInput
{
    /**
     * Sanitizar todas las entradas del request
     */
    public function handle(Request $request, Closure $next)
    {
        $input = $request->all();
        
        $sanitized = $this->sanitizeArray($input);
        
        $request->merge($sanitized);
        
        return $next($request);
    }
    
    /**
     * Sanitizar recursivamente un array
     */
    private function sanitizeArray(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->sanitizeArray($value);
            } elseif (is_string($value)) {
                // Sanitizar string: remover tags HTML y caracteres peligrosos
                $data[$key] = $this->sanitizeString($value);
            }
        }
        
        return $data;
    }
    
    /**
     * Sanitizar una cadena de texto
     */
    private function sanitizeString(string $value): string
    {
        // Eliminar tags HTML y PHP
        $value = strip_tags($value);
        
        // Convertir caracteres especiales a entidades HTML
        $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        
        // Remover caracteres de control
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);
        
        // Trimear espacios
        $value = trim($value);
        
        return $value;
    }
}
