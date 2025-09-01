<?php

namespace Database\Seeders;

use App\Models\TechnicalReport;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TechnicalReportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Limpiar tabla antes de sembrar
        DB::table('technical_reports')->truncate();

        $this->command->info('Creando reportes técnicos de ejemplo...');

        // Asegurar que existan ubicaciones
        if (Location::count() === 0) {
            $this->call(LocationSeeder::class);
        }

        // Asegurar que exista al menos un usuario
        if (User::count() === 0) {
            User::factory()->create([
                'name' => 'Admin User',
                'email' => 'admin@geoespacial.com',
            ]);
        }

        // Crear reportes de diferentes tipos
        $this->createReportsByType();
        
        // Crear reportes con diferentes proveedores
        $this->createReportsByProvider();
        
        // Crear reportes con diferentes estados
        $this->createReportsByStatus();
        
        // Crear reportes recientes para demostración
        $this->createRecentReports();

        $this->command->info('✅ Reportes técnicos creados exitosamente');
        $this->command->info('Total reportes: ' . TechnicalReport::count());
    }

    /**
     * Crea reportes de cada tipo disponible
     */
    private function createReportsByType(): void
    {
        $this->command->info('Creando reportes por tipo...');

        foreach (array_keys(TechnicalReport::TYPES) as $type) {
            // 2-3 reportes de cada tipo
            TechnicalReport::factory()
                ->count(rand(2, 3))
                ->ofType($type)
                ->completed()
                ->create();

            $this->command->info("  ✓ Reportes de tipo '{$type}' creados");
        }
    }

    /**
     * Crea reportes con diferentes proveedores LLM
     */
    private function createReportsByProvider(): void
    {
        $this->command->info('Creando reportes por proveedor...');

        foreach (array_keys(TechnicalReport::PROVIDERS) as $provider) {
            TechnicalReport::factory()
                ->count(rand(3, 5))
                ->withProvider($provider)
                ->completed()
                ->create();

            $this->command->info("  ✓ Reportes con proveedor '{$provider}' creados");
        }
    }

    /**
     * Crea reportes con diferentes estados
     */
    private function createReportsByStatus(): void
    {
        $this->command->info('Creando reportes por estado...');

        // Reportes completados (mayoría)
        TechnicalReport::factory()
            ->count(15)
            ->completed()
            ->create();

        // Reportes en caché
        TechnicalReport::factory()
            ->count(8)
            ->cached()
            ->create();

        // Algunos reportes fallidos
        TechnicalReport::factory()
            ->count(3)
            ->failed()
            ->create();

        $this->command->info('  ✓ Reportes con diferentes estados creados');
    }

    /**
     * Crea reportes recientes para demostración
     */
    private function createRecentReports(): void
    {
        $this->command->info('Creando reportes recientes...');

        // Reportes de las últimas 24 horas
        TechnicalReport::factory()
            ->count(10)
            ->recent()
            ->completed()
            ->create();

        $this->command->info('  ✓ Reportes recientes creados');
    }

    /**
     * Crea reportes específicos para demostración
     */
    private function createDemoReports(): void
    {
        $this->command->info('Creando reportes de demostración...');

        $locations = Location::take(3)->get();
        $user = User::first();

        if ($locations->isNotEmpty() && $user) {
            // Reporte meteorológico para Madrid
            TechnicalReport::factory()->create([
                'title' => 'Análisis Meteorológico Detallado - Madrid',
                'type' => 'weather',
                'llm_provider' => 'openai',
                'status' => 'completed',
                'location_id' => $locations->first()->id,
                'user_id' => $user->id,
                'content' => $this->getMadridWeatherReport(),
                'summary' => 'Análisis meteorológico comprensivo de Madrid con condiciones estables y proyecciones favorables.',
            ]);

            // Reporte de rendimiento del sistema
            TechnicalReport::factory()->create([
                'title' => 'Evaluación de Performance del Sistema Geoespacial',
                'type' => 'performance',
                'llm_provider' => 'anthropic',
                'status' => 'completed',
                'user_id' => $user->id,
                'content' => $this->getPerformanceReport(),
                'summary' => 'Sistema operando dentro de parámetros normales con oportunidades de optimización identificadas.',
            ]);

            // Reporte espacial
            if ($locations->count() > 1) {
                TechnicalReport::factory()->create([
                    'title' => 'Análisis Geoespacial - Características Territoriales',
                    'type' => 'spatial',
                    'llm_provider' => 'simulation',
                    'status' => 'cached',
                    'location_id' => $locations->get(1)->id,
                    'user_id' => $user->id,
                    'content' => $this->getSpatialReport(),
                    'summary' => 'Análisis territorial detallado con identificación de características geográficas relevantes.',
                ]);
            }
        }

        $this->command->info('  ✓ Reportes de demostración creados');
    }

    /**
     * Reporte meteorológico de ejemplo para Madrid
     */
    private function getMadridWeatherReport(): string
    {
        return "# Análisis Meteorológico Detallado - Madrid

## Resumen Ejecutivo

Este reporte presenta un análisis comprensivo de las condiciones meteorológicas actuales en Madrid, España, incluyendo tendencias recientes y proyecciones a corto plazo.

## Condiciones Actuales

### Temperatura y Sensación Térmica
- **Temperatura actual**: 22°C
- **Sensación térmica**: 24°C
- **Rango diario**: 18°C - 28°C
- **Variación estacional**: Dentro de parámetros normales

### Humedad y Presión Atmosférica
- **Humedad relativa**: 65%
- **Presión atmosférica**: 1018 hPa
- **Punto de rocío**: 15°C
- **Visibilidad**: 10+ km

## Análisis de Tendencias

### Patrones Meteorológicos
Las condiciones actuales muestran un patrón típico de transición estacional con:
- Estabilidad en la presión atmosférica
- Variaciones normales de temperatura diurna
- Humedad en rangos confortables

### Predicción a 48 Horas
- **Temperatura**: 19°C - 26°C
- **Probabilidad de precipitación**: 20%
- **Dirección del viento**: SO, 12 km/h
- **Índice UV**: 6 (Moderado)

## Factores de Impacto

### Calidad del Aire
- **Índice de calidad del aire**: 68 (Moderado)
- **PM2.5**: 15 μg/m³
- **PM10**: 28 μg/m³
- **Ozono troposférico**: 85 μg/m³

### Condiciones para Actividades
- **Actividades al aire libre**: Recomendadas
- **Deportes**: Condiciones óptimas
- **Agricultura**: Favorables para riego moderado

## Alertas y Recomendaciones

### Alertas Meteorológicas
- No hay alertas meteorológicas activas
- Condiciones estables previstas

### Recomendaciones
1. **Hidratación**: Mantener niveles adecuados de hidratación
2. **Protección solar**: Usar protector solar durante las horas centrales
3. **Vestimenta**: Ropa ligera y transpirable recomendada
4. **Ventilación**: Aprovechar las horas frescas para ventilación natural

## Contexto Histórico

### Comparación Estacional
Las condiciones actuales se encuentran:
- 2°C por encima de la media histórica para esta fecha
- Dentro del rango de variabilidad normal
- Consistente con patrones de cambio climático regional

## Conclusiones

El análisis meteorológico revela condiciones estables y favorables en Madrid, con indicadores dentro de rangos normales y proyecciones positivas para los próximos días. Se recomienda continuar el monitoreo de la calidad del aire y mantener las precauciones estándar para la protección solar.

---
*Análisis generado automáticamente el " . now()->format('d/m/Y H:i') . " usando datos meteorológicos integrados*";
    }

    /**
     * Reporte de rendimiento de ejemplo
     */
    private function getPerformanceReport(): string
    {
        return "# Evaluación de Performance del Sistema Geoespacial

## Resumen Ejecutivo

Este reporte evalúa el rendimiento actual del sistema geoespacial, identificando áreas de optimización y proporcionando recomendaciones para mejorar la eficiencia operacional.

## Métricas de Rendimiento

### Recursos del Sistema
- **CPU promedio**: 45%
- **Memoria utilizada**: 3.2GB / 8GB (40%)
- **Espacio en disco**: 125GB / 500GB (25%)
- **Carga del sistema**: 1.2 (Óptima)

### Base de Datos
- **Conexiones activas**: 12 / 100
- **Tiempo promedio de consulta**: 85ms
- **Caché hit ratio**: 92%
- **Índices utilizados**: 94%

### APIs y Servicios Externos
- **OpenWeather API**: 
  - Latencia promedio: 145ms
  - Tasa de éxito: 99.2%
  - Requests/día: 1,247
- **PostGIS queries**:
  - Tiempo promedio: 32ms
  - Consultas complejas: 156ms

## Análisis de Carga

### Patrones de Uso
- **Hora pico**: 14:00 - 16:00
- **Usuarios concurrentes máximo**: 45
- **Throughput máximo**: 125 req/min
- **Tiempo de respuesta promedio**: 230ms

### Distribución de Recursos
- **Consultas geoespaciales**: 35%
- **APIs meteorológicas**: 28%
- **Generación de reportes**: 22%
- **Caché y optimización**: 15%

## Estado de Servicios

### Disponibilidad
- **API principal**: 99.8% uptime
- **Base de datos**: 100% uptime
- **Servicios externos**: 98.5% uptime
- **WebSocket connections**: 97.2% estabilidad

### Alertas Recientes
- Sin alertas críticas en las últimas 24h
- 2 alertas menores de latencia resueltas
- Mantenimiento programado completado exitosamente

## Análisis de Cuellos de Botella

### Identificación de Problemas
1. **Consultas geoespaciales complejas**: Algunas consultas PostGIS superan los 500ms
2. **Caché de reportes LLM**: Tasa de hit del 75%, puede mejorarse
3. **Conexiones WebSocket**: Ocasionales desconexiones durante picos de carga

### Áreas de Mejora
- Optimización de índices geoespaciales
- Implementación de caché distribuido
- Balanceador de carga para WebSockets

## Recomendaciones de Optimización

### Inmediatas (1-2 semanas)
1. **Índices PostGIS**: Crear índices específicos para consultas frecuentes
2. **Caché Redis**: Expandir configuración de caché para reportes LLM
3. **Query optimization**: Revisar y optimizar las 5 consultas más lentas

### Mediano plazo (1-2 meses)
1. **Scaling horizontal**: Implementar réplicas de lectura para PostGIS
2. **CDN**: Configurar CDN para assets estáticos
3. **Monitoring avanzado**: Implementar métricas detalladas por endpoint

### Largo plazo (3-6 meses)
1. **Microservicios**: Evaluar separación de servicios críticos
2. **Auto-scaling**: Implementar escalado automático basado en carga
3. **Disaster recovery**: Plan completo de recuperación ante desastres

## Costos y ROI

### Optimizaciones Propuestas
- **Costo estimado**: $2,500 USD
- **Reducción esperada en latencia**: 35%
- **Aumento de throughput**: 50%
- **ROI estimado**: 6 meses

## Conclusiones

El sistema geoespacial muestra un rendimiento sólido con oportunidades claras de optimización. Las métricas indican operación estable dentro de parámetros aceptables, con potencial significativo de mejora mediante las optimizaciones propuestas.

---
*Reporte de rendimiento generado el " . now()->format('d/m/Y H:i') . "*";
    }

    /**
     * Reporte espacial de ejemplo
     */
    private function getSpatialReport(): string
    {
        return "# Análisis Geoespacial - Características Territoriales

## Resumen Ejecutivo

Este análisis geoespacial examina las características territoriales y geográficas de la región seleccionada, proporcionando insights sobre topografía, recursos naturales y factores de ubicación estratégica.

## Datos de Ubicación

### Coordenadas de Referencia
- **Sistema de coordenadas**: WGS84 (EPSG:4326)
- **Latitud**: 40.4168° N
- **Longitud**: 3.7038° W
- **Elevación promedio**: 667 msnm
- **Precisión**: ±2 metros

### Características Topográficas
- **Tipo de terreno**: Meseta continental
- **Pendiente máxima**: 8.5°
- **Pendiente promedio**: 3.2°
- **Orientación predominante**: Sur-Oeste
- **Drenaje natural**: Bueno

## Análisis de Proximidad

### Centros Urbanos Cercanos
- **Madrid capital**: 15 km (NE)
- **Alcorcón**: 8 km (SW)
- **Móstoles**: 12 km (S)
- **Getafe**: 18 km (SE)

### Infraestructura de Transporte
- **Autopistas**: A-5 (2.5 km), M-40 (4 km)
- **Ferrocarril**: Línea C-5 Cercanías (1.8 km)
- **Aeropuerto**: Madrid-Barajas (35 km)
- **Metro**: Línea 12 (3.2 km)

### Recursos Hídricos
- **Río Guadarrama**: 6 km al oeste
- **Río Manzanares**: 12 km al norte
- **Embalses cercanos**: 2 dentro de 25 km
- **Acuíferos**: Detrítico terciario

## Evaluación de Riesgos Geoespaciales

### Riesgos Naturales
- **Riesgo sísmico**: Muy bajo (intensidad < IV)
- **Riesgo de inundación**: Bajo (zona elevada)
- **Riesgo de deslizamiento**: Mínimo (pendientes suaves)
- **Riesgo de erosión**: Bajo-moderado

### Factores Climáticos
- **Zona climática**: Continental mediterráneo
- **Precipitación anual**: 450-500 mm
- **Temperatura media**: 14.5°C
- **Vientos dominantes**: NO y SO

## Cobertura del Suelo

### Uso Actual del Territorio
- **Urbano residencial**: 45%
- **Espacios verdes**: 28%
- **Infraestructura**: 15%
- **Agrícola/Natural**: 12%

### Calidad del Suelo
- **Tipo de suelo**: Cambisol cálcico
- **pH**: 7.8-8.2 (ligeramente alcalino)
- **Capacidad agrícola**: Media-alta
- **Permeabilidad**: Moderada

## Análisis de Conectividad

### Accesibilidad
- **Índice de accesibilidad**: 8.5/10
- **Tiempo a servicios básicos**: < 10 minutos
- **Conectividad digital**: Fibra óptica disponible
- **Transporte público**: Excelente cobertura

### Servicios Territoriales
- **Hospitales**: 3 dentro de 15 km
- **Centros educativos**: 12 dentro de 5 km
- **Centros comerciales**: 4 grandes superficies
- **Servicios públicos**: Cobertura completa

## Potencial de Desarrollo

### Oportunidades
1. **Desarrollo residencial**: Zonas disponibles con buena conectividad
2. **Espacios verdes**: Potencial para parques urbanos
3. **Infraestructura smart**: Base tecnológica sólida
4. **Turismo sostenible**: Patrimonio natural cercano

### Limitaciones
1. **Densidad urbana**: Algunas áreas próximas a saturación
2. **Recursos hídricos**: Disponibilidad limitada en verano
3. **Contaminación atmosférica**: Niveles urbanos típicos
4. **Ruido**: Proximidad a vías de alta capacidad

## Recomendaciones Territoriales

### Planificación Urbana
1. **Densificación controlada**: Optimizar uso del suelo disponible
2. **Corredores verdes**: Conectar espacios naturales existentes
3. **Movilidad sostenible**: Potenciar transporte público y ciclista
4. **Gestión del agua**: Sistemas de captación y reutilización

### Sostenibilidad Ambiental
1. **Biodiversidad urbana**: Conservar y crear hábitats
2. **Calidad del aire**: Monitoreo y medidas de mejora
3. **Gestión de residuos**: Sistemas de economía circular
4. **Eficiencia energética**: Integración de renovables

## Conclusiones

El análisis geoespacial revela un territorio con características favorables para el desarrollo sostenible, excelente conectividad y recursos naturales adecuados. Las recomendaciones se enfocan en optimizar el uso del espacio disponible mientras se preservan los valores ambientales.

---
*Análisis geoespacial completado el " . now()->format('d/m/Y H:i') . "*";
    }
}
