# 🌍 Dashboard Geoespacial en Tiempo Real

Sistema de monitoreo meteorológico con datos en tiempo real y WebSockets.

## 🚀 Inicio Rápido

### 1. Iniciar el sistema
```bash
cd c:\wamp64\www\geoespacial
docker-compose up -d
```

### 2. Abrir en navegador
**http://localhost:8080/**


## ✅ Verificar que funciona

- ✅ Ver mapa con ubicaciones
- ✅ Temperaturas se actualizan solas cada 60 segundos
- ✅ Agregar nuevas ubicaciones (aparecen al instante)
- ✅ Ver cuántos usuarios están conectados

## 🔧 Comandos Útiles

### Ver usuarios conectados ahora mismo
```bash
curl http://localhost:8080/api/websocket/stats -UseBasicParsing | ConvertFrom-Json
```

### Ver temperaturas actuales
```bash
curl http://localhost:8080/api/geospatial/summary -UseBasicParsing | ConvertFrom-Json | Select-Object -ExpandProperty data | ForEach-Object { "{0}: {1}°C" -f $_.location.name, $_.weather.temperature }
```

### Actualizar temperaturas manualmente
```bash
docker-compose exec app php artisan geospatial:realtime --once
```

### Si algo no funciona
```bash
docker-compose restart worker
docker-compose restart queue-worker
docker-compose restart reverb
```

## � Características

- 🌡️ Datos meteorológicos en tiempo real
- 🗺️ Mapa interactivo
- � Monitor de usuarios conectados
- 🔄 Actualizaciones automáticas
- � Funciona en móviles

## 🎯 Estado: ✅ FUNCIONAL

Todo está configurado y listo para usar.