@extends('layouts.app')

@section('title', 'Reportes Técnicos')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Encabezado -->
    <div class="bg-white rounded-lg shadow-sm border p-6 mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">
                    <i class="fas fa-file-alt text-blue-600 mr-3"></i>
                    Reportes Técnicos
                </h1>
                <p class="text-gray-600">
                    Genera y gestiona reportes técnicos automatizados con inteligencia artificial
                </p>
            </div>
            <div class="mt-4 md:mt-0">
                <a href="{{ route('reports.create') }}" 
                   class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-plus mr-2"></i>
                    Generar Nuevo Reporte
                </a>
            </div>
        </div>
    </div>

    <!-- Estadísticas -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-sm border p-6">
            <div class="flex items-center">
                <div class="p-3 bg-blue-100 rounded-lg">
                    <i class="fas fa-file-alt text-blue-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Reportes</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['total_reports'] ?? 0 }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border p-6">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 rounded-lg">
                    <i class="fas fa-calendar-day text-green-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Hoy</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['reports_today'] ?? 0 }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border p-6">
            <div class="flex items-center">
                <div class="p-3 bg-yellow-100 rounded-lg">
                    <i class="fas fa-clock text-yellow-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Tiempo Promedio</p>
                    <p class="text-2xl font-bold text-gray-900">
                        {{ isset($stats['avg_generation_time']) && is_numeric($stats['avg_generation_time']) ? round((float)$stats['avg_generation_time'], 2) . 's' : 'N/A' }}
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border p-6">
            <div class="flex items-center">
                <div class="p-3 bg-purple-100 rounded-lg">
                    <i class="fas fa-chart-line text-purple-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Últimas 24h</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['recent_reports'] ?? 0 }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Distribución por Tipo y Proveedor -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Por Tipo -->
        <div class="bg-white rounded-lg shadow-sm border p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-chart-pie text-blue-600 mr-2"></i>
                Reportes por Tipo
            </h3>
            <div class="space-y-3">
                @foreach(\App\Models\TechnicalReport::TYPES as $key => $name)
                    @php
                        $count = $stats['reports_by_type'][$key] ?? 0;
                        $total = max($stats['total_reports'], 1);
                        $percentage = round(($count / $total) * 100, 1);
                    @endphp
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-700">{{ $name }}</span>
                        <div class="flex items-center space-x-2">
                            <div class="w-20 bg-gray-200 rounded-full h-2">
                                <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $percentage }}%"></div>
                            </div>
                            <span class="text-xs text-gray-500 w-8">{{ $count }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Por Proveedor -->
        <div class="bg-white rounded-lg shadow-sm border p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-robot text-green-600 mr-2"></i>
                Reportes por Proveedor
            </h3>
            <div class="space-y-3">
                @foreach(\App\Models\TechnicalReport::PROVIDERS as $key => $name)
                    @php
                        $count = $stats['reports_by_provider'][$key] ?? 0;
                        $total = max($stats['total_reports'], 1);
                        $percentage = round(($count / $total) * 100, 1);
                        $colorClass = $key === 'simulation' ? 'bg-gray-600' : ($key === 'openai' ? 'bg-green-600' : 'bg-purple-600');
                    @endphp
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-700">{{ $name }}</span>
                        <div class="flex items-center space-x-2">
                            <div class="w-20 bg-gray-200 rounded-full h-2">
                                <div class="{{ $colorClass }} h-2 rounded-full" style="width: {{ $percentage }}%"></div>
                            </div>
                            <span class="text-xs text-gray-500 w-8">{{ $count }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Lista de Reportes -->
    <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
        <div class="p-6 border-b">
            <h3 class="text-lg font-semibold text-gray-900">
                <i class="fas fa-list text-gray-600 mr-2"></i>
                Reportes Recientes
            </h3>
        </div>

        @if($reports->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Reporte
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Tipo
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Proveedor
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Estado
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Creado
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Acciones
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($reports as $report)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $report->title }}
                                        </div>
                                        @if($report->location)
                                            <div class="text-sm text-gray-500">
                                                <i class="fas fa-map-marker-alt mr-1"></i>
                                                {{ $report->location->name }}
                                            </div>
                                        @endif
                                        @if($report->summary)
                                            <div class="text-xs text-gray-400 mt-1 max-w-xs truncate">
                                                {{ $report->summary }}
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        @if($report->type === 'weather') bg-blue-100 text-blue-800
                                        @elseif($report->type === 'spatial') bg-green-100 text-green-800
                                        @elseif($report->type === 'performance') bg-purple-100 text-purple-800
                                        @elseif($report->type === 'environmental') bg-yellow-100 text-yellow-800
                                        @elseif($report->type === 'predictive') bg-pink-100 text-pink-800
                                        @else bg-gray-100 text-gray-800
                                        @endif">
                                        {{ $report->type_name }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        @if($report->llm_provider === 'openai') bg-green-100 text-green-800
                                        @elseif($report->llm_provider === 'anthropic') bg-purple-100 text-purple-800
                                        @else bg-gray-100 text-gray-800
                                        @endif">
                                        {{ $report->provider_name }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        @if($report->status === 'completed') bg-green-100 text-green-800
                                        @elseif($report->status === 'cached') bg-blue-100 text-blue-800
                                        @elseif($report->status === 'generating') bg-yellow-100 text-yellow-800
                                        @elseif($report->status === 'failed') bg-red-100 text-red-800
                                        @else bg-gray-100 text-gray-800
                                        @endif">
                                        @if($report->status === 'completed')
                                            <i class="fas fa-check-circle mr-1"></i>
                                        @elseif($report->status === 'cached')
                                            <i class="fas fa-save mr-1"></i>
                                        @elseif($report->status === 'generating')
                                            <i class="fas fa-spinner fa-spin mr-1"></i>
                                        @elseif($report->status === 'failed')
                                            <i class="fas fa-exclamation-triangle mr-1"></i>
                                        @endif
                                        {{ $report->status_name }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <div>{{ $report->created_at->format('d/m/Y') }}</div>
                                    <div class="text-xs">{{ $report->created_at->format('H:i') }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end space-x-2">
                                        <a href="{{ route('reports.show', $report) }}" 
                                           class="text-blue-600 hover:text-blue-900 transition-colors"
                                           title="Ver reporte">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        
                                        <form action="{{ route('reports.regenerate', $report) }}" 
                                              method="POST" class="inline">
                                            @csrf
                                            <button type="submit" 
                                                    class="text-green-600 hover:text-green-900 transition-colors"
                                                    title="Regenerar reporte"
                                                    onclick="return confirm('¿Estás seguro de que quieres regenerar este reporte?')">
                                                <i class="fas fa-redo"></i>
                                            </button>
                                        </form>
                                        
                                        <form action="{{ route('reports.destroy', $report) }}" 
                                              method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" 
                                                    class="text-red-600 hover:text-red-900 transition-colors"
                                                    title="Eliminar reporte"
                                                    onclick="return confirm('¿Estás seguro de que quieres eliminar este reporte?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            @if($reports->hasPages())
                <div class="px-6 py-4 border-t">
                    {{ $reports->links() }}
                </div>
            @endif
        @else
            <!-- Estado vacío -->
            <div class="text-center py-12">
                <div class="mx-auto h-24 w-24 text-gray-400">
                    <i class="fas fa-file-alt text-6xl"></i>
                </div>
                <h3 class="mt-4 text-lg font-medium text-gray-900">No hay reportes</h3>
                <p class="mt-2 text-sm text-gray-500">
                    Comienza generando tu primer reporte técnico con IA
                </p>
                <div class="mt-6">
                    <a href="{{ route('reports.create') }}" 
                       class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-plus mr-2"></i>
                        Generar Primer Reporte
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@push('styles')
<style>
    .transition-colors {
        transition: color 0.15s ease-in-out;
    }
</style>
@endpush
