@extends('layouts.app')

@section('title', $report->title)

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Encabezado del Reporte -->
    <div class="bg-white rounded-lg shadow-sm border overflow-hidden mb-6">
        <div class="p-6 border-b bg-gradient-to-r from-blue-50 to-purple-50">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex-1">
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">
                        {{ $report->title }}
                    </h1>
                    <div class="flex flex-wrap items-center gap-4 text-sm text-gray-600">
                        <div class="flex items-center">
                            <i class="fas fa-clock mr-1"></i>
                            Generado {{ $report->created_at->diffForHumans() }}
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-user mr-1"></i>
                            {{ $report->user->name ?? 'Sistema' }}
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-robot mr-1"></i>
                            {{ $report->provider_display_name }}
                        </div>
                        @if($report->location)
                            <div class="flex items-center">
                                <i class="fas fa-map-marker-alt mr-1"></i>
                                {{ $report->location->name }}, {{ $report->location->country }}
                            </div>
                        @endif
                    </div>
                </div>
                
                <div class="flex flex-wrap items-center gap-3">
                    <!-- Badge del Tipo -->
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                        @if($report->type === 'weather') bg-orange-100 text-orange-800
                        @elseif($report->type === 'spatial') bg-green-100 text-green-800
                        @elseif($report->type === 'performance') bg-purple-100 text-purple-800
                        @elseif($report->type === 'environmental') bg-green-100 text-green-800
                        @elseif($report->type === 'predictive') bg-pink-100 text-pink-800
                        @else bg-blue-100 text-blue-800
                        @endif">
                        <i class="fas 
                            @if($report->type === 'weather') fa-cloud-sun
                            @elseif($report->type === 'spatial') fa-map-marked-alt
                            @elseif($report->type === 'performance') fa-tachometer-alt
                            @elseif($report->type === 'environmental') fa-leaf
                            @elseif($report->type === 'predictive') fa-crystal-ball
                            @else fa-chart-line
                            @endif mr-1"></i>
                        {{ $report->type_display_name }}
                    </span>

                    <!-- Acciones -->
                    <div class="flex items-center gap-2">
                        <form action="{{ route('reports.regenerate', $report) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" 
                                    class="inline-flex items-center px-3 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors"
                                    onclick="return confirm('¿Estás seguro de regenerar este reporte? Se creará una nueva versión.')">
                                <i class="fas fa-sync-alt mr-1"></i>
                                Regenerar
                            </button>
                        </form>
                        
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" 
                                    class="inline-flex items-center px-3 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                                <i class="fas fa-download mr-1"></i>
                                Exportar
                                <i class="fas fa-chevron-down ml-1"></i>
                            </button>
                            
                            <div x-show="open" @click.away="open = false" 
                                 class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border z-10">
                                <div class="py-1">
                                    <a href="{{ route('reports.export', ['report' => $report, 'format' => 'pdf']) }}" 
                                       class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        <i class="fas fa-file-pdf text-red-500 mr-2"></i>
                                        Exportar como PDF
                                    </a>
                                    <a href="{{ route('reports.export', ['report' => $report, 'format' => 'json']) }}" 
                                       class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        <i class="fas fa-file-code text-blue-500 mr-2"></i>
                                        Exportar como JSON
                                    </a>
                                    <button onclick="copyToClipboard()" 
                                            class="w-full flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        <i class="fas fa-copy text-gray-500 mr-2"></i>
                                        Copiar al Portapapeles
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <a href="{{ route('reports.index') }}" 
                           class="inline-flex items-center px-3 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                            <i class="fas fa-arrow-left mr-1"></i>
                            Volver
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-4 gap-6">
        <!-- Contenido Principal -->
        <div class="xl:col-span-3">
            <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                <div class="p-6 border-b bg-gray-50">
                    <h2 class="text-lg font-semibold text-gray-900">
                        <i class="fas fa-file-alt text-blue-600 mr-2"></i>
                        Contenido del Reporte
                    </h2>
                </div>
                
                <div class="p-6">
                    <div class="prose prose-lg max-w-none">
                        {!! $report->formatted_content !!}
                    </div>
                </div>
            </div>

            @if($report->metadata && count($report->metadata) > 0)
                <!-- Metadatos -->
                <div class="bg-white rounded-lg shadow-sm border overflow-hidden mt-6">
                    <div class="p-6 border-b bg-gray-50">
                        <h2 class="text-lg font-semibold text-gray-900">
                            <i class="fas fa-info-circle text-green-600 mr-2"></i>
                            Información Adicional
                        </h2>
                    </div>
                    
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($report->metadata as $key => $value)
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <div class="text-sm font-medium text-gray-600 uppercase tracking-wide">
                                        {{ str_replace('_', ' ', $key) }}
                                    </div>
                                    <div class="mt-1 text-gray-900">
                                        @if(is_array($value))
                                            <pre class="text-sm bg-white p-2 rounded border overflow-x-auto">{{ json_encode($value, JSON_PRETTY_PRINT) }}</pre>
                                        @else
                                            {{ $value }}
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Panel Lateral -->
        <div class="space-y-6">
            <!-- Información del Reporte -->
            <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                <div class="p-4 border-b bg-gray-50">
                    <h3 class="font-semibold text-gray-900">
                        <i class="fas fa-info text-blue-600 mr-1"></i>
                        Detalles
                    </h3>
                </div>
                <div class="p-4 space-y-3">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">ID:</span>
                        <span class="font-mono text-gray-900">#{{ $report->id }}</span>
                    </div>
                    
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Tipo:</span>
                        <span class="font-medium text-gray-900">{{ $report->type_display_name }}</span>
                    </div>
                    
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Proveedor:</span>
                        <span class="font-medium text-gray-900">{{ $report->provider_display_name }}</span>
                    </div>
                    
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Creado:</span>
                        <span class="text-gray-900">{{ $report->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Actualizado:</span>
                        <span class="text-gray-900">{{ $report->updated_at->format('d/m/Y H:i') }}</span>
                    </div>
                    
                    @if($report->location)
                        <div class="pt-2 border-t">
                            <div class="text-sm text-gray-600 mb-1">Ubicación:</div>
                            <div class="text-sm font-medium text-gray-900">{{ $report->location->name }}</div>
                            <div class="text-xs text-gray-500">
                                {{ $report->location->city }}, {{ $report->location->country }}
                            </div>
                            <div class="text-xs text-gray-500 mt-1">
                                <i class="fas fa-map-pin mr-1"></i>
                                {{ number_format($report->location->latitude, 4) }}, {{ number_format($report->location->longitude, 4) }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Estadísticas del Contenido -->
            <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                <div class="p-4 border-b bg-gray-50">
                    <h3 class="font-semibold text-gray-900">
                        <i class="fas fa-chart-bar text-green-600 mr-1"></i>
                        Estadísticas
                    </h3>
                </div>
                <div class="p-4 space-y-3">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Palabras:</span>
                        <span class="font-medium text-gray-900">{{ str_word_count(strip_tags($report->content)) }}</span>
                    </div>
                    
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Caracteres:</span>
                        <span class="font-medium text-gray-900">{{ strlen($report->content) }}</span>
                    </div>
                    
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Párrafos:</span>
                        <span class="font-medium text-gray-900">{{ substr_count($report->content, '\n\n') + 1 }}</span>
                    </div>
                    
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Tiempo de lectura:</span>
                        <span class="font-medium text-gray-900">~{{ ceil(str_word_count(strip_tags($report->content)) / 200) }} min</span>
                    </div>
                </div>
            </div>

            <!-- Reportes Relacionados -->
            @if($relatedReports && $relatedReports->count() > 0)
                <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                    <div class="p-4 border-b bg-gray-50">
                        <h3 class="font-semibold text-gray-900">
                            <i class="fas fa-link text-purple-600 mr-1"></i>
                            Reportes Relacionados
                        </h3>
                    </div>
                    <div class="p-4 space-y-3">
                        @foreach($relatedReports as $related)
                            <a href="{{ route('reports.show', $related) }}" 
                               class="block p-3 rounded-lg border hover:bg-gray-50 transition-colors">
                                <div class="font-medium text-sm text-gray-900 mb-1">{{ $related->title }}</div>
                                <div class="text-xs text-gray-500">
                                    {{ $related->type_display_name }} • {{ $related->created_at->diffForHumans() }}
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Acciones Rápidas -->
            <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                <div class="p-4 border-b bg-gray-50">
                    <h3 class="font-semibold text-gray-900">
                        <i class="fas fa-bolt text-yellow-600 mr-1"></i>
                        Acciones Rápidas
                    </h3>
                </div>
                <div class="p-4 space-y-2">
                    <a href="{{ route('reports.create') }}?type={{ $report->type }}&location_id={{ $report->location_id }}" 
                       class="w-full inline-flex items-center justify-center px-3 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-plus mr-2"></i>
                        Crear Similar
                    </a>
                    
                    <button onclick="shareReport()" 
                            class="w-full inline-flex items-center justify-center px-3 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                        <i class="fas fa-share mr-2"></i>
                        Compartir
                    </button>
                    
                    <button onclick="printReport()" 
                            class="w-full inline-flex items-center justify-center px-3 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 transition-colors">
                        <i class="fas fa-print mr-2"></i>
                        Imprimir
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
function copyToClipboard() {
    const content = @json($report->content);
    navigator.clipboard.writeText(content).then(function() {
        alert('¡Contenido copiado al portapapeles!');
    }, function(err) {
        console.error('Error al copiar: ', err);
        alert('Error al copiar el contenido');
    });
}

function shareReport() {
    if (navigator.share) {
        navigator.share({
            title: '{{ $report->title }}',
            text: 'Reporte técnico generado con IA',
            url: window.location.href
        }).catch(console.error);
    } else {
        copyToClipboard();
        alert('Enlace copiado al portapapeles');
    }
}

function printReport() {
    window.print();
}

// Estilo para impresión
const printStyles = `
    @media print {
        .no-print { display: none !important; }
        .print-break { page-break-before: always; }
        body { font-size: 12pt; line-height: 1.4; }
        .prose { max-width: none; }
    }
`;
const style = document.createElement('style');
style.textContent = printStyles;
document.head.appendChild(style);
</script>
@endpush

@push('styles')
<style>
    .prose {
        line-height: 1.7;
    }
    .prose h1, .prose h2, .prose h3 {
        margin-top: 2rem;
        margin-bottom: 1rem;
        font-weight: 600;
    }
    .prose h1 { font-size: 1.5rem; }
    .prose h2 { font-size: 1.25rem; }
    .prose h3 { font-size: 1.125rem; }
    .prose p {
        margin-bottom: 1rem;
        text-align: justify;
    }
    .prose ul, .prose ol {
        margin: 1rem 0;
        padding-left: 1.5rem;
    }
    .prose li {
        margin-bottom: 0.5rem;
    }
    .prose strong {
        font-weight: 600;
        color: #374151;
    }
    .prose em {
        font-style: italic;
        color: #6B7280;
    }
</style>
@endpush
