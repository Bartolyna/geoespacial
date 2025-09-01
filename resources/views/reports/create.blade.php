@extends('layouts.app')

@section('title', 'Generar Nuevo Reporte')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Encabezado -->
    <div class="bg-white rounded-lg shadow-sm border p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">
                    <i class="fas fa-magic text-purple-600 mr-3"></i>
                    Generar Nuevo Reporte
                </h1>
                <p class="text-gray-600">
                    Crea un reporte técnico automatizado utilizando inteligencia artificial
                </p>
            </div>
            <a href="{{ route('reports.index') }}" 
               class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 font-medium rounded-lg hover:bg-gray-200 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>
                Volver a Reportes
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Formulario Principal -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                <div class="p-6 border-b bg-gray-50">
                    <h2 class="text-lg font-semibold text-gray-900">
                        <i class="fas fa-cog text-blue-600 mr-2"></i>
                        Configuración del Reporte
                    </h2>
                </div>

                <form action="{{ route('reports.store') }}" method="POST" class="p-6 space-y-6" id="reportForm">
                    @csrf

                    <!-- Tipo de Reporte -->
                    <div>
                        <label for="type" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-list-ul mr-1"></i>
                            Tipo de Reporte *
                        </label>
                        <select name="type" id="type" required 
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Selecciona un tipo de reporte</option>
                            @foreach($types as $key => $name)
                                <option value="{{ $key }}" {{ old('type') === $key ? 'selected' : '' }}>
                                    {{ $name }}
                                </option>
                            @endforeach
                        </select>
                        @error('type')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        
                        <!-- Descripción del tipo seleccionado -->
                        <div id="typeDescription" class="mt-2 text-sm text-gray-600 hidden">
                            <div class="p-3 bg-blue-50 rounded-lg border border-blue-200">
                                <span id="typeDescriptionText"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Proveedor LLM -->
                    <div>
                        <label for="provider" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-robot mr-1"></i>
                            Proveedor de IA *
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            @foreach($providers as $key => $name)
                                <label class="relative cursor-pointer">
                                    <input type="radio" name="provider" value="{{ $key }}" 
                                           {{ old('provider', 'simulation') === $key ? 'checked' : '' }}
                                           class="sr-only peer" required>
                                    <div class="p-4 border-2 border-gray-200 rounded-lg peer-checked:border-blue-500 peer-checked:bg-blue-50 hover:border-gray-300 transition-colors">
                                        <div class="flex items-center justify-center mb-2">
                                            @if($key === 'simulation')
                                                <i class="fas fa-code text-gray-600 text-xl"></i>
                                            @elseif($key === 'openai')
                                                <i class="fas fa-brain text-green-600 text-xl"></i>
                                            @else
                                                <i class="fas fa-robot text-purple-600 text-xl"></i>
                                            @endif
                                        </div>
                                        <div class="text-center">
                                            <div class="font-medium text-gray-900">{{ $name }}</div>
                                            <div class="text-xs text-gray-500 mt-1">
                                                @if($key === 'simulation')
                                                    Gratuito • Rápido
                                                @elseif($key === 'openai')
                                                    GPT • Avanzado
                                                @else
                                                    Claude • Preciso
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                        @error('provider')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Ubicación -->
                    <div>
                        <label for="location_id" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-map-marker-alt mr-1"></i>
                            Ubicación (Opcional)
                        </label>
                        <select name="location_id" id="location_id" 
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Sin ubicación específica</option>
                            @foreach($locations as $location)
                                <option value="{{ $location->id }}" {{ old('location_id') == $location->id ? 'selected' : '' }}>
                                    {{ $location->name }} - {{ $location->city }}, {{ $location->country }}
                                </option>
                            @endforeach
                        </select>
                        @error('location_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500">
                            Selecciona una ubicación para reportes específicos de clima o análisis espacial
                        </p>
                    </div>

                    <!-- Título Personalizado -->
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-heading mr-1"></i>
                            Título Personalizado (Opcional)
                        </label>
                        <input type="text" name="title" id="title" value="{{ old('title') }}"
                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                               placeholder="Se generará automáticamente si se deja vacío">
                        @error('title')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Botones de Acción -->
                    <div class="flex flex-col sm:flex-row gap-3 pt-6 border-t">
                        <button type="submit" 
                                class="flex-1 inline-flex justify-center items-center px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"
                                id="generateBtn">
                            <i class="fas fa-magic mr-2"></i>
                            <span id="generateBtnText">Generar Reporte</span>
                        </button>
                        <a href="{{ route('reports.index') }}" 
                           class="inline-flex justify-center items-center px-6 py-3 bg-gray-100 text-gray-700 font-medium rounded-lg hover:bg-gray-200 transition-colors">
                            <i class="fas fa-times mr-2"></i>
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Panel de Información -->
        <div class="space-y-6">
            <!-- Información sobre Tipos -->
            <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                <div class="p-4 bg-gray-50 border-b">
                    <h3 class="font-semibold text-gray-900">
                        <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                        Tipos de Reporte
                    </h3>
                </div>
                <div class="p-4 space-y-3">
                    <div class="text-sm">
                        <div class="font-medium text-gray-900 mb-1">
                            <i class="fas fa-chart-line text-blue-600 mr-1"></i>
                            General
                        </div>
                        <p class="text-gray-600 text-xs">Análisis comprensivo del sistema y métricas generales</p>
                    </div>
                    
                    <div class="text-sm">
                        <div class="font-medium text-gray-900 mb-1">
                            <i class="fas fa-cloud-sun text-orange-600 mr-1"></i>
                            Meteorológico
                        </div>
                        <p class="text-gray-600 text-xs">Análisis de datos climáticos y predicciones meteorológicas</p>
                    </div>
                    
                    <div class="text-sm">
                        <div class="font-medium text-gray-900 mb-1">
                            <i class="fas fa-map-marked-alt text-green-600 mr-1"></i>
                            Espacial
                        </div>
                        <p class="text-gray-600 text-xs">Evaluación geoespacial y análisis territorial</p>
                    </div>
                    
                    <div class="text-sm">
                        <div class="font-medium text-gray-900 mb-1">
                            <i class="fas fa-tachometer-alt text-purple-600 mr-1"></i>
                            Rendimiento
                        </div>
                        <p class="text-gray-600 text-xs">Métricas de performance y optimización del sistema</p>
                    </div>
                    
                    <div class="text-sm">
                        <div class="font-medium text-gray-900 mb-1">
                            <i class="fas fa-leaf text-green-600 mr-1"></i>
                            Ambiental
                        </div>
                        <p class="text-gray-600 text-xs">Análisis de impacto ambiental y sostenibilidad</p>
                    </div>
                    
                    <div class="text-sm">
                        <div class="font-medium text-gray-900 mb-1">
                            <i class="fas fa-crystal-ball text-pink-600 mr-1"></i>
                            Predictivo
                        </div>
                        <p class="text-gray-600 text-xs">Modelado de tendencias y análisis de forecasting</p>
                    </div>
                </div>
            </div>

            <!-- Información sobre Proveedores -->
            <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                <div class="p-4 bg-gray-50 border-b">
                    <h3 class="font-semibold text-gray-900">
                        <i class="fas fa-robot text-purple-600 mr-2"></i>
                        Proveedores de IA
                    </h3>
                </div>
                <div class="p-4 space-y-4">
                    <div class="text-sm">
                        <div class="flex items-center justify-between mb-1">
                            <span class="font-medium text-gray-900">Simulación</span>
                            <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">Gratis</span>
                        </div>
                        <p class="text-gray-600 text-xs">Generación rápida y gratuita para desarrollo y pruebas</p>
                    </div>
                    
                    <div class="text-sm">
                        <div class="flex items-center justify-between mb-1">
                            <span class="font-medium text-gray-900">OpenAI GPT</span>
                            <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">Avanzado</span>
                        </div>
                        <p class="text-gray-600 text-xs">Tecnología GPT para análisis detallados y precisos</p>
                    </div>
                    
                    <div class="text-sm">
                        <div class="flex items-center justify-between mb-1">
                            <span class="font-medium text-gray-900">Anthropic Claude</span>
                            <span class="px-2 py-1 bg-purple-100 text-purple-800 text-xs rounded-full">Preciso</span>
                        </div>
                        <p class="text-gray-600 text-xs">Claude para reportes técnicos detallados y confiables</p>
                    </div>
                </div>
            </div>

            <!-- Consejos -->
            <div class="bg-blue-50 rounded-lg border border-blue-200 p-4">
                <h4 class="font-semibold text-blue-900 mb-2">
                    <i class="fas fa-lightbulb text-blue-600 mr-1"></i>
                    Consejos
                </h4>
                <ul class="text-sm text-blue-800 space-y-1">
                    <li class="flex items-start">
                        <i class="fas fa-check text-blue-600 mr-2 mt-0.5 text-xs"></i>
                        Para reportes meteorológicos, selecciona una ubicación específica
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check text-blue-600 mr-2 mt-0.5 text-xs"></i>
                        Usa simulación para pruebas rápidas y gratuitas
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check text-blue-600 mr-2 mt-0.5 text-xs"></i>
                        Los reportes se almacenan en caché para optimizar costos
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.getElementById('type');
    const typeDescription = document.getElementById('typeDescription');
    const typeDescriptionText = document.getElementById('typeDescriptionText');
    const generateBtn = document.getElementById('generateBtn');
    const generateBtnText = document.getElementById('generateBtnText');
    const reportForm = document.getElementById('reportForm');

    // Descripciones detalladas de tipos
    const typeDescriptions = {
        'general': 'Análisis técnico comprensivo que incluye métricas generales del sistema, indicadores de rendimiento y recomendaciones estratégicas.',
        'weather': 'Reporte meteorológico detallado con análisis de condiciones actuales, tendencias históricas y predicciones a corto plazo.',
        'spatial': 'Evaluación geoespacial que analiza características territoriales, proximidad a puntos de interés y factores de ubicación.',
        'performance': 'Análisis de rendimiento del sistema con métricas de CPU, memoria, base de datos y recomendaciones de optimización.',
        'environmental': 'Evaluación de impacto ambiental incluyendo calidad del aire, recursos hídricos y factores de sostenibilidad.',
        'predictive': 'Análisis predictivo con modelado de tendencias, forecasting y evaluación de riesgos futuros.'
    };

    // Mostrar descripción del tipo seleccionado
    typeSelect.addEventListener('change', function() {
        const selectedType = this.value;
        if (selectedType && typeDescriptions[selectedType]) {
            typeDescriptionText.textContent = typeDescriptions[selectedType];
            typeDescription.classList.remove('hidden');
        } else {
            typeDescription.classList.add('hidden');
        }
    });

    // Manejar envío del formulario
    reportForm.addEventListener('submit', function(e) {
        generateBtn.disabled = true;
        generateBtn.classList.add('opacity-50');
        generateBtnText.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Generando...';
    });

    // Trigger inicial si hay un tipo seleccionado
    if (typeSelect.value) {
        typeSelect.dispatchEvent(new Event('change'));
    }
});
</script>
@endpush

@push('styles')
<style>
    .transition-colors {
        transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out;
    }
</style>
@endpush
