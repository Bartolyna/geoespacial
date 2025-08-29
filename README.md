# 🌍 Dashboard Geoespacial en Tiempo Real

Sistema de monitoreo meteorológico con datos en tiempo real, WebSockets y **PostGIS**.

## 🚀 Inicio Rápido

### 1. Iniciar el sistema
```bash
cd c:\wamp64\www\geoespacial
docker-compose up -d
```

### 2. Abrir en navegador
**http://localhost:8080/**

¡Listo! El sistema está funcionando.

## ✅ Verificar que funciona

- ✅ Ver mapa con ubicaciones
- ✅ Temperaturas se actualizan solas cada 60 segundos
- ✅ Agregar nuevas ubicaciones (aparecen al instante)
- ✅ Ver cuántos usuarios están conectados
- ✅ **Búsquedas geoespaciales avanzadas con PostGIS**

## 🗺️ PostGIS - Funcionalidades Geoespaciales

### Probar PostGIS
```powershell
# Información del sistema PostGIS
Invoke-WebRequest -Uri "http://localhost:8080/api/postgis/info" | ConvertFrom-Json

# Buscar ubicaciones en 50km de Nueva York
Invoke-WebRequest -Uri "http://localhost:8080/api/postgis/search/radius" -Method POST -Headers @{"Content-Type"="application/json"} -Body '{"latitude": 40.7128, "longitude": -74.0060, "radius": 50000}' | ConvertFrom-Json

# Estadísticas geográficas
Invoke-WebRequest -Uri "http://localhost:8080/api/postgis/stats" | ConvertFrom-Json
```

### 📖 [Ver documentación completa de PostGIS](docs/POSTGIS.md)

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

## 🛡️ Probar Seguridad (3 comandos)

### 1. Ver reporte de seguridad
```bash
docker-compose exec app php artisan security:report --days=1
```

### 2. Probar rate limiting (debe bloquear tras varias requests)
```bash
for ($i=1; $i -le 15; $i++) { docker-compose exec nginx curl -X GET http://localhost/api/geospatial/locations -s -w "%{http_code}" -o /dev/null; Start-Sleep -Milliseconds 100 }
```

### 3. Probar validación anti-XSS (debe rechazar)
```bash
$xss = '{"name":"<script>alert(1)</script>","city":"Madrid","country":"Spain","latitude":40.4168,"longitude":-3.7038}'; docker-compose exec nginx curl -X POST http://localhost/api/geospatial/locations -H "Content-Type: application/json" -d $xss -s
```

**Resultados esperados:** Rate limiting = Error 429, XSS = Error validación

### Si algo no funciona
```bash
docker-compose restart worker
docker-compose restart queue-worker
docker-compose restart reverb
```

## 📋 Características

- 🌡️ Datos meteorológicos en tiempo real
- 🗺️ Mapa interactivo
- 👥 Monitor de usuarios conectados
- 🔄 Actualizaciones automáticas
- 📱 Funciona en móviles
- 🛡️ Sistema de seguridad completo

## 🎯 Estado: ✅ COMPLETAMENTE FUNCIONAL
