@extends('layouts.app')

@section('title', 'Datos Meteorológicos')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Encabezado -->
    <div class="bg-white rounded-lg shadow-sm border p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">
                    <i class="fas fa-cloud-sun text-orange-600 mr-3"></i>
                    Datos Meteorológicos
                </h1>
                <p class="text-gray-600">
                    Monitoreo en tiempo real de condiciones climáticas globales
                </p>
            </div>
            <div class="flex items-center space-x-2">
                <span class="px-3 py-1 bg-orange-100 text-orange-800 text-sm font-medium rounded-full">
                    <i class="fas fa-sync-alt mr-1"></i>
                    Actualizado hace {{ $recentWeatherData->first()?->created_at->diffForHumans() ?? 'N/A' }}
                </span>
            </div>
        </div>
    </div>

    <!-- Estadísticas Rápidas -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow-sm border p-6">
            <div class="flex items-center">
                <div class="p-3 bg-blue-100 rounded-lg">
                    <i class="fas fa-thermometer-half text-blue-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Temperatura Promedio</p>
                    <p class="text-2xl font-bold text-gray-900">
                        {{ $recentWeatherData->avg('temperature') ? round($recentWeatherData->avg('temperature'), 1) . '°C' : 'N/A' }}
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border p-6">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 rounded-lg">
                    <i class="fas fa-map-marker-alt text-green-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Ubicaciones Activas</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $locations->count() }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border p-6">
            <div class="flex items-center">
                <div class="p-3 bg-yellow-100 rounded-lg">
                    <i class="fas fa-cloud text-yellow-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Condición Dominante</p>
                    <p class="text-lg font-bold text-gray-900">
                        {{ $recentWeatherData->groupBy('weather')->sortByDesc(function($group) { return $group->count(); })->keys()->first() ?? 'N/A' }}
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border p-6">
            <div class="flex items-center">
                <div class="p-3 bg-purple-100 rounded-lg">
                    <i class="fas fa-database text-purple-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Registros Hoy</p>
                    <p class="text-2xl font-bold text-gray-900">
                        {{ $recentWeatherData->where('created_at', '>=', today())->count() }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Datos en Tiempo Real -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Ubicaciones con Clima -->
            <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                <div class="p-6 border-b bg-gray-50">
                    <h2 class="text-lg font-semibold text-gray-900">
                        <i class="fas fa-globe-americas text-blue-600 mr-2"></i>
                        Condiciones Actuales por Ubicación
                    </h2>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Ubicación
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Temperatura
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Condición
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Última Actualización
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($locations as $location)
                                @php
                                    $weather = $location->latestWeatherData;
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">{{ $location->name }}</div>
                                            <div class="text-sm text-gray-500">{{ $location->city }}, {{ $location->country }}</div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($weather)
                                            <span class="text-lg font-semibold text-gray-900">
                                                {{ round($weather->temperature, 1) }}°C
                                            </span>
                                        @else
                                            <span class="text-gray-400">N/A</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($weather)
                                            <div class="flex items-center">
                                                <i class="fas 
                                                    @if($weather->weather === 'Clear') fa-sun text-yellow-500
                                                    @elseif($weather->weather === 'Clouds') fa-cloud text-gray-500
                                                    @elseif($weather->weather === 'Rain') fa-cloud-rain text-blue-500
                                                    @elseif($weather->weather === 'Snow') fa-snowflake text-blue-300
                                                    @else fa-question text-gray-400
                                                    @endif mr-2"></i>
                                                <span class="text-sm text-gray-900">{{ $weather->weather }}</span>
                                            </div>
                                        @else
                                            <span class="text-gray-400">N/A</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $weather ? $weather->created_at->diffForHumans() : 'N/A' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Actividad Reciente -->
            <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                <div class="p-6 border-b bg-gray-50">
                    <h2 class="text-lg font-semibold text-gray-900">
                        <i class="fas fa-history text-green-600 mr-2"></i>
                        Actividad Reciente
                    </h2>
                </div>
                
                <div class="p-6">
                    <div class="space-y-4">
                        @foreach($recentWeatherData->take(8) as $data)
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div class="flex items-center">
                                    <div class="p-2 bg-blue-100 rounded-lg mr-3">
                                        <i class="fas 
                                            @if($data->weather === 'Clear') fa-sun text-yellow-600
                                            @elseif($data->weather === 'Clouds') fa-cloud text-gray-600
                                            @elseif($data->weather === 'Rain') fa-cloud-rain text-blue-600
                                            @else fa-thermometer-half text-blue-600
                                            @endif"></i>
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $data->location->name ?? 'Ubicación desconocida' }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {{ $data->created_at->format('H:i') }} - {{ $data->weather }}
                                        </div>
                                    </div>
                                </div>
                                <div class="text-lg font-semibold text-gray-900">
                                    {{ round($data->temperature, 1) }}°C
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Panel Lateral -->
        <div class="space-y-6">
            <!-- Controles -->
            <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                <div class="p-4 border-b bg-gray-50">
                    <h3 class="font-semibold text-gray-900">
                        <i class="fas fa-cogs text-purple-600 mr-1"></i>
                        Controles
                    </h3>
                </div>
                <div class="p-4 space-y-3">
                    <button onclick="refreshWeatherData()" 
                            class="w-full flex items-center justify-center px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-sync-alt mr-2"></i>
                        Actualizar Datos
                    </button>
                    
                    <button class="w-full flex items-center justify-center px-3 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                        <i class="fas fa-download mr-2"></i>
                        Exportar CSV
                    </button>
                    
                    <button class="w-full flex items-center justify-center px-3 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                        <i class="fas fa-chart-line mr-2"></i>
                        Ver Gráficas
                    </button>
                </div>
            </div>

            <!-- Filtros -->
            <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                <div class="p-4 border-b bg-gray-50">
                    <h3 class="font-semibold text-gray-900">
                        <i class="fas fa-filter text-indigo-600 mr-1"></i>
                        Filtros
                    </h3>
                </div>
                <div class="p-4 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Rango de Temperatura</label>
                        <div class="flex space-x-2">
                            <input type="number" placeholder="Min" class="flex-1 text-sm border border-gray-300 rounded-md px-2 py-1">
                            <input type="number" placeholder="Max" class="flex-1 text-sm border border-gray-300 rounded-md px-2 py-1">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Condición Climática</label>
                        <select class="w-full text-sm border border-gray-300 rounded-md px-2 py-1">
                            <option value="">Todas</option>
                            <option value="Clear">Despejado</option>
                            <option value="Clouds">Nublado</option>
                            <option value="Rain">Lluvia</option>
                            <option value="Snow">Nieve</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Período</label>
                        <select class="w-full text-sm border border-gray-300 rounded-md px-2 py-1">
                            <option value="1h">Última hora</option>
                            <option value="24h" selected>Últimas 24 horas</option>
                            <option value="7d">Última semana</option>
                            <option value="30d">Último mes</option>
                        </select>
                    </div>
                    
                    <button class="w-full px-3 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                        <i class="fas fa-search mr-1"></i>
                        Aplicar Filtros
                    </button>
                </div>
            </div>

            <!-- Alertas -->
            <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                <div class="p-4 border-b bg-gray-50">
                    <h3 class="font-semibold text-gray-900">
                        <i class="fas fa-exclamation-triangle text-yellow-600 mr-1"></i>
                        Alertas Climáticas
                    </h3>
                </div>
                <div class="p-4">
                    <div class="text-center text-gray-500 py-4">
                        <i class="fas fa-shield-alt text-2xl mb-2"></i>
                        <p class="text-sm">No hay alertas activas</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function refreshWeatherData() {
    // Mostrar indicador de carga
    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Actualizando...';
    button.disabled = true;
    
    // Simular actualización
    setTimeout(() => {
        button.innerHTML = originalText;
        button.disabled = false;
        alert('Datos meteorológicos actualizados');
        // Aquí se haría la llamada AJAX real
    }, 2000);
}

// Auto-refresh cada 5 minutos
setInterval(() => {
    console.log('Auto-refresh de datos meteorológicos');
    // Aquí se implementaría la actualización automática
}, 300000);
</script>
@endpush
