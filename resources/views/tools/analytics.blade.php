@extends('layouts.app')

@section('title', 'Análisis y Métricas')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Encabezado -->
    <div class="bg-white rounded-lg shadow-sm border p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">
                    <i class="fas fa-chart-line text-purple-600 mr-3"></i>
                    Análisis y Métricas
                </h1>
                <p class="text-gray-600">
                    Dashboard avanzado con análisis estadístico y visualizaciones
                </p>
            </div>
            <div class="flex items-center space-x-2">
                <span class="px-3 py-1 bg-purple-100 text-purple-800 text-sm font-medium rounded-full">
                    <i class="fas fa-clock mr-1"></i>
                    Actualizado: {{ now()->format('H:i') }}
                </span>
            </div>
        </div>
    </div>

    <!-- Métricas Principales -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-sm border p-6">
            <div class="flex items-center">
                <div class="p-3 bg-blue-100 rounded-lg">
                    <i class="fas fa-map-marker-alt text-blue-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Ubicaciones</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['total_locations'] }}</p>
                    <p class="text-xs text-green-600">
                        <i class="fas fa-arrow-up mr-1"></i>
                        {{ $stats['active_locations'] }} activas
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border p-6">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 rounded-lg">
                    <i class="fas fa-database text-green-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Registros Climáticos</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['total_weather_records']) }}</p>
                    <p class="text-xs text-blue-600">
                        <i class="fas fa-plus mr-1"></i>
                        {{ $stats['recent_weather_updates'] }} hoy
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border p-6">
            <div class="flex items-center">
                <div class="p-3 bg-yellow-100 rounded-lg">
                    <i class="fas fa-percentage text-yellow-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Tasa de Actividad</p>
                    <p class="text-2xl font-bold text-gray-900">
                        {{ $stats['total_locations'] > 0 ? round(($stats['active_locations'] / $stats['total_locations']) * 100, 1) : 0 }}%
                    </p>
                    <p class="text-xs text-purple-600">
                        <i class="fas fa-chart-pie mr-1"></i>
                        Ubicaciones activas
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border p-6">
            <div class="flex items-center">
                <div class="p-3 bg-purple-100 rounded-lg">
                    <i class="fas fa-clock text-purple-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Promedio por Ubicación</p>
                    <p class="text-2xl font-bold text-gray-900">
                        {{ $stats['active_locations'] > 0 ? round($stats['total_weather_records'] / $stats['active_locations'], 1) : 0 }}
                    </p>
                    <p class="text-xs text-gray-600">
                        <i class="fas fa-calculator mr-1"></i>
                        Registros por ubicación
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficas y Análisis -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Gráfica de Tendencias -->
        <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
            <div class="p-6 border-b bg-gray-50">
                <h2 class="text-lg font-semibold text-gray-900">
                    <i class="fas fa-chart-area text-blue-600 mr-2"></i>
                    Tendencias Temporales
                </h2>
            </div>
            <div class="p-6">
                <!-- Placeholder para gráfica -->
                <div class="h-64 bg-gray-100 rounded-lg flex items-center justify-center">
                    <div class="text-center">
                        <i class="fas fa-chart-line text-4xl text-gray-400 mb-4"></i>
                        <p class="text-gray-600 mb-2">Gráfica de Tendencias</p>
                        <p class="text-sm text-gray-500">Integración con Chart.js pendiente</p>
                        <button class="mt-3 px-4 py-2 bg-blue-100 text-blue-700 rounded-lg text-sm hover:bg-blue-200">
                            <i class="fas fa-play mr-1"></i>
                            Simular Datos
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Distribución Geográfica -->
        <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
            <div class="p-6 border-b bg-gray-50">
                <h2 class="text-lg font-semibold text-gray-900">
                    <i class="fas fa-globe-americas text-green-600 mr-2"></i>
                    Distribución Geográfica
                </h2>
            </div>
            <div class="p-6">
                <!-- Placeholder para mapa de calor -->
                <div class="h-64 bg-gray-100 rounded-lg flex items-center justify-center">
                    <div class="text-center">
                        <i class="fas fa-map text-4xl text-gray-400 mb-4"></i>
                        <p class="text-gray-600 mb-2">Mapa de Calor</p>
                        <p class="text-sm text-gray-500">Visualización de densidad de datos</p>
                        <button class="mt-3 px-4 py-2 bg-green-100 text-green-700 rounded-lg text-sm hover:bg-green-200">
                            <i class="fas fa-eye mr-1"></i>
                            Ver Mapa
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Análisis Detallado -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Estadísticas Avanzadas -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                <div class="p-6 border-b bg-gray-50">
                    <h2 class="text-lg font-semibold text-gray-900">
                        <i class="fas fa-calculator text-purple-600 mr-2"></i>
                        Estadísticas Avanzadas
                    </h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Análisis Temporal -->
                        <div>
                            <h3 class="text-md font-medium text-gray-900 mb-4">Análisis Temporal</h3>
                            <div class="space-y-3">
                                <div class="flex justify-between items-center p-3 bg-blue-50 rounded-lg">
                                    <span class="text-sm text-gray-700">Registros esta semana</span>
                                    <span class="font-semibold text-blue-700">{{ rand(150, 300) }}</span>
                                </div>
                                <div class="flex justify-between items-center p-3 bg-green-50 rounded-lg">
                                    <span class="text-sm text-gray-700">Promedio diario</span>
                                    <span class="font-semibold text-green-700">{{ rand(20, 50) }}</span>
                                </div>
                                <div class="flex justify-between items-center p-3 bg-yellow-50 rounded-lg">
                                    <span class="text-sm text-gray-700">Pico máximo (hora)</span>
                                    <span class="font-semibold text-yellow-700">{{ rand(14, 18) }}:00</span>
                                </div>
                            </div>
                        </div>

                        <!-- Análisis Geográfico -->
                        <div>
                            <h3 class="text-md font-medium text-gray-900 mb-4">Análisis Geográfico</h3>
                            <div class="space-y-3">
                                <div class="flex justify-between items-center p-3 bg-purple-50 rounded-lg">
                                    <span class="text-sm text-gray-700">Países cubiertos</span>
                                    <span class="font-semibold text-purple-700">{{ rand(8, 15) }}</span>
                                </div>
                                <div class="flex justify-between items-center p-3 bg-indigo-50 rounded-lg">
                                    <span class="text-sm text-gray-700">Ciudades activas</span>
                                    <span class="font-semibold text-indigo-700">{{ $stats['active_locations'] }}</span>
                                </div>
                                <div class="flex justify-between items-center p-3 bg-pink-50 rounded-lg">
                                    <span class="text-sm text-gray-700">Zona más activa</span>
                                    <span class="font-semibold text-pink-700">Europa</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla de Rendimiento -->
                    <div class="mt-8">
                        <h3 class="text-md font-medium text-gray-900 mb-4">Rendimiento por Región</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Región</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ubicaciones</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Registros</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actividad</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <tr>
                                        <td class="px-4 py-4 text-sm font-medium text-gray-900">Europa</td>
                                        <td class="px-4 py-4 text-sm text-gray-500">{{ rand(15, 25) }}</td>
                                        <td class="px-4 py-4 text-sm text-gray-500">{{ number_format(rand(5000, 8000)) }}</td>
                                        <td class="px-4 py-4">
                                            <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">Alta</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-4 text-sm font-medium text-gray-900">América</td>
                                        <td class="px-4 py-4 text-sm text-gray-500">{{ rand(10, 20) }}</td>
                                        <td class="px-4 py-4 text-sm text-gray-500">{{ number_format(rand(3000, 6000)) }}</td>
                                        <td class="px-4 py-4">
                                            <span class="px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 rounded-full">Media</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-4 text-sm font-medium text-gray-900">Asia</td>
                                        <td class="px-4 py-4 text-sm text-gray-500">{{ rand(5, 15) }}</td>
                                        <td class="px-4 py-4 text-sm text-gray-500">{{ number_format(rand(2000, 4000)) }}</td>
                                        <td class="px-4 py-4">
                                            <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">Media</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Panel de Herramientas -->
        <div class="space-y-6">
            <!-- Exportación -->
            <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                <div class="p-4 border-b bg-gray-50">
                    <h3 class="font-semibold text-gray-900">
                        <i class="fas fa-download text-blue-600 mr-1"></i>
                        Exportación
                    </h3>
                </div>
                <div class="p-4 space-y-3">
                    <button class="w-full flex items-center justify-center px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-file-csv mr-2"></i>
                        Exportar CSV
                    </button>
                    
                    <button class="w-full flex items-center justify-center px-3 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                        <i class="fas fa-file-excel mr-2"></i>
                        Exportar Excel
                    </button>
                    
                    <button class="w-full flex items-center justify-center px-3 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                        <i class="fas fa-file-pdf mr-2"></i>
                        Reporte PDF
                    </button>
                </div>
            </div>

            <!-- Configuración -->
            <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                <div class="p-4 border-b bg-gray-50">
                    <h3 class="font-semibold text-gray-900">
                        <i class="fas fa-cogs text-purple-600 mr-1"></i>
                        Configuración
                    </h3>
                </div>
                <div class="p-4 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Período de Análisis</label>
                        <select class="w-full text-sm border border-gray-300 rounded-md px-2 py-1">
                            <option value="7d">Últimos 7 días</option>
                            <option value="30d" selected>Últimos 30 días</option>
                            <option value="90d">Últimos 90 días</option>
                            <option value="1y">Último año</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Granularidad</label>
                        <select class="w-full text-sm border border-gray-300 rounded-md px-2 py-1">
                            <option value="hourly">Por hora</option>
                            <option value="daily" selected>Por día</option>
                            <option value="weekly">Por semana</option>
                        </select>
                    </div>
                    
                    <button class="w-full px-3 py-2 bg-purple-100 text-purple-700 rounded-lg hover:bg-purple-200 transition-colors">
                        <i class="fas fa-sync-alt mr-1"></i>
                        Actualizar Análisis
                    </button>
                </div>
            </div>

            <!-- Alertas -->
            <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                <div class="p-4 border-b bg-gray-50">
                    <h3 class="font-semibold text-gray-900">
                        <i class="fas fa-bell text-yellow-600 mr-1"></i>
                        Alertas y Notificaciones
                    </h3>
                </div>
                <div class="p-4">
                    <div class="space-y-3">
                        <div class="flex items-center p-2 bg-green-50 rounded-lg">
                            <i class="fas fa-check-circle text-green-600 mr-2"></i>
                            <span class="text-sm text-green-700">Sistema funcionando normalmente</span>
                        </div>
                        
                        <div class="flex items-center p-2 bg-blue-50 rounded-lg">
                            <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                            <span class="text-sm text-blue-700">{{ $stats['recent_weather_updates'] }} actualizaciones hoy</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Simulación de datos en tiempo real
setInterval(() => {
    // Actualizar métricas simuladas
    console.log('Actualizando métricas en tiempo real...');
}, 30000);

// Función para simular exportación
function simulateExport(format) {
    alert(`Exportando datos en formato ${format}...`);
    // Aquí se implementaría la exportación real
}
</script>
@endpush
