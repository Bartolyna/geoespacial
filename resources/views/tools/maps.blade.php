@extends('layouts.app')

@section('title', 'Mapas Interactivos')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Encabezado -->
    <div class="bg-white rounded-lg shadow-sm border p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">
                    <i class="fas fa-map text-green-600 mr-3"></i>
                    Mapas Interactivos
                </h1>
                <p class="text-gray-600">
                    Visualización geoespacial de ubicaciones y datos en tiempo real
                </p>
            </div>
            <div class="flex items-center space-x-2">
                <span class="px-3 py-1 bg-green-100 text-green-800 text-sm font-medium rounded-full">
                    <i class="fas fa-map-marker-alt mr-1"></i>
                    {{ $locations->count() }} Ubicaciones
                </span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Mapa Principal -->
        <div class="lg:col-span-3">
            <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                <div class="p-4 border-b bg-gray-50">
                    <h2 class="text-lg font-semibold text-gray-900">
                        <i class="fas fa-globe text-blue-600 mr-2"></i>
                        Mapa Mundial
                    </h2>
                </div>
                
                <div class="relative">
                    <!-- Placeholder del mapa -->
                    <div id="map-container" class="w-full h-96 bg-gray-100 flex items-center justify-center">
                        <div class="text-center">
                            <i class="fas fa-map text-4xl text-gray-400 mb-4"></i>
                            <p class="text-gray-600 mb-2">Mapa Interactivo</p>
                            <p class="text-sm text-gray-500">Integración con Leaflet/OpenStreetMap pendiente</p>
                        </div>
                    </div>
                    
                    <!-- Controles del mapa -->
                    <div class="absolute top-4 right-4 space-y-2">
                        <button class="bg-white border border-gray-300 rounded-lg p-2 shadow-sm hover:bg-gray-50">
                            <i class="fas fa-plus text-gray-600"></i>
                        </button>
                        <button class="bg-white border border-gray-300 rounded-lg p-2 shadow-sm hover:bg-gray-50">
                            <i class="fas fa-minus text-gray-600"></i>
                        </button>
                        <button class="bg-white border border-gray-300 rounded-lg p-2 shadow-sm hover:bg-gray-50">
                            <i class="fas fa-crosshairs text-gray-600"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Información del mapa -->
                <div class="p-4 border-t bg-gray-50">
                    <div class="flex items-center justify-between text-sm text-gray-600">
                        <div class="flex items-center space-x-4">
                            <span>
                                <i class="fas fa-layer-group mr-1"></i>
                                Capas: OpenStreetMap
                            </span>
                            <span>
                                <i class="fas fa-ruler mr-1"></i>
                                Proyección: WGS84
                            </span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button class="px-3 py-1 bg-blue-100 text-blue-700 rounded-lg text-xs font-medium hover:bg-blue-200">
                                <i class="fas fa-download mr-1"></i>
                                Exportar
                            </button>
                            <button class="px-3 py-1 bg-green-100 text-green-700 rounded-lg text-xs font-medium hover:bg-green-200">
                                <i class="fas fa-share mr-1"></i>
                                Compartir
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Panel Lateral -->
        <div class="space-y-6">
            <!-- Ubicaciones -->
            <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                <div class="p-4 border-b bg-gray-50">
                    <h3 class="font-semibold text-gray-900">
                        <i class="fas fa-list text-purple-600 mr-1"></i>
                        Ubicaciones Activas
                    </h3>
                </div>
                <div class="max-h-64 overflow-y-auto">
                    @foreach($locations as $location)
                        <div class="p-3 border-b border-gray-100 hover:bg-gray-50 cursor-pointer location-item" 
                             data-lat="{{ $location->latitude }}" 
                             data-lng="{{ $location->longitude }}"
                             data-name="{{ $location->name }}">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="font-medium text-sm text-gray-900">{{ $location->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $location->city }}, {{ $location->country }}</div>
                                </div>
                                <div class="text-xs text-gray-400">
                                    <i class="fas fa-map-pin"></i>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Herramientas de Mapa -->
            <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                <div class="p-4 border-b bg-gray-50">
                    <h3 class="font-semibold text-gray-900">
                        <i class="fas fa-tools text-orange-600 mr-1"></i>
                        Herramientas
                    </h3>
                </div>
                <div class="p-4 space-y-3">
                    <button class="w-full flex items-center px-3 py-2 bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 transition-colors">
                        <i class="fas fa-search-location mr-2"></i>
                        Buscar Ubicación
                    </button>
                    
                    <button class="w-full flex items-center px-3 py-2 bg-green-50 text-green-700 rounded-lg hover:bg-green-100 transition-colors">
                        <i class="fas fa-plus-circle mr-2"></i>
                        Agregar Marcador
                    </button>
                    
                    <button class="w-full flex items-center px-3 py-2 bg-purple-50 text-purple-700 rounded-lg hover:bg-purple-100 transition-colors">
                        <i class="fas fa-draw-polygon mr-2"></i>
                        Dibujar Área
                    </button>
                    
                    <button class="w-full flex items-center px-3 py-2 bg-yellow-50 text-yellow-700 rounded-lg hover:bg-yellow-100 transition-colors">
                        <i class="fas fa-ruler mr-2"></i>
                        Medir Distancia
                    </button>
                </div>
            </div>

            <!-- Capas -->
            <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                <div class="p-4 border-b bg-gray-50">
                    <h3 class="font-semibold text-gray-900">
                        <i class="fas fa-layer-group text-indigo-600 mr-1"></i>
                        Capas del Mapa
                    </h3>
                </div>
                <div class="p-4 space-y-3">
                    <label class="flex items-center">
                        <input type="checkbox" checked class="form-checkbox text-blue-600">
                        <span class="ml-2 text-sm text-gray-700">OpenStreetMap</span>
                    </label>
                    
                    <label class="flex items-center">
                        <input type="checkbox" class="form-checkbox text-blue-600">
                        <span class="ml-2 text-sm text-gray-700">Datos Meteorológicos</span>
                    </label>
                    
                    <label class="flex items-center">
                        <input type="checkbox" class="form-checkbox text-blue-600">
                        <span class="ml-2 text-sm text-gray-700">Zona Climática</span>
                    </label>
                    
                    <label class="flex items-center">
                        <input type="checkbox" class="form-checkbox text-blue-600">
                        <span class="ml-2 text-sm text-gray-700">Densidad Poblacional</span>
                    </label>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Simulación de interacción con ubicaciones
    const locationItems = document.querySelectorAll('.location-item');
    
    locationItems.forEach(item => {
        item.addEventListener('click', function() {
            const name = this.dataset.name;
            const lat = this.dataset.lat;
            const lng = this.dataset.lng;
            
            // Remover selección anterior
            locationItems.forEach(loc => loc.classList.remove('bg-blue-50'));
            
            // Agregar selección actual
            this.classList.add('bg-blue-50');
            
            // Simular zoom al mapa
            console.log(`Navegando a: ${name} (${lat}, ${lng})`);
            
            // Aquí se integraría con Leaflet o Google Maps
            alert(`Centrar mapa en: ${name}`);
        });
    });
});
</script>
@endpush

@push('styles')
<style>
    .form-checkbox {
        width: 1rem;
        height: 1rem;
        border-radius: 0.25rem;
    }
    
    .location-item:hover {
        transform: translateX(2px);
        transition: transform 0.2s ease;
    }
</style>
@endpush
