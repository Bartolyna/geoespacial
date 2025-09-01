<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $report->title }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            border-bottom: 2px solid #e5e5e5;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .title {
            font-size: 24px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 10px;
        }
        
        .meta {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 5px;
        }
        
        .content {
            font-size: 16px;
            line-height: 1.8;
        }
        
        .content h1 {
            font-size: 20px;
            color: #1f2937;
            margin-top: 30px;
            margin-bottom: 15px;
        }
        
        .content h2 {
            font-size: 18px;
            color: #374151;
            margin-top: 25px;
            margin-bottom: 12px;
        }
        
        .content h3 {
            font-size: 16px;
            color: #4b5563;
            margin-top: 20px;
            margin-bottom: 10px;
        }
        
        .content p {
            margin-bottom: 15px;
            text-align: justify;
        }
        
        .content ul, .content ol {
            margin: 15px 0;
            padding-left: 30px;
        }
        
        .content li {
            margin-bottom: 8px;
        }
        
        .footer {
            border-top: 1px solid #e5e5e5;
            padding-top: 20px;
            margin-top: 40px;
            font-size: 12px;
            color: #6b7280;
            text-align: center;
        }
        
        @media print {
            body {
                font-size: 12pt;
                line-height: 1.4;
            }
            .header {
                page-break-after: avoid;
            }
            h1, h2, h3 {
                page-break-after: avoid;
            }
            p {
                widows: 2;
                orphans: 2;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">{{ $report->title }}</div>
        
        <div class="meta">
            <strong>Tipo:</strong> {{ $report->type_display_name }}
        </div>
        
        <div class="meta">
            <strong>Generado por:</strong> {{ $report->provider_display_name }}
        </div>
        
        <div class="meta">
            <strong>Fecha:</strong> {{ $report->created_at->format('d/m/Y H:i') }}
        </div>
        
        @if($report->location)
            <div class="meta">
                <strong>Ubicación:</strong> {{ $report->location->name }}, {{ $report->location->city }}, {{ $report->location->country }}
            </div>
        @endif
        
        @if($report->user)
            <div class="meta">
                <strong>Usuario:</strong> {{ $report->user->name }}
            </div>
        @endif
    </div>
    
    <div class="content">
        {!! $report->formatted_content !!}
    </div>
    
    <div class="footer">
        <p>
            Reporte generado automáticamente por el Sistema Geoespacial<br>
            ID del reporte: #{{ $report->id }} | {{ now()->format('d/m/Y H:i') }}
        </p>
    </div>
</body>
</html>
