# 🌍 Sistema Geoespacial con IA - Dashboard en Tiempo Real

Sistema avanzado de monitoreo meteorológico con **inteligencia artificial**, datos en tiempo real, WebSockets y **PostGIS**. Incluye generación automática de reportes técnicos usando OpenAI y Anthropic Claude.

## 🚀 Inicio Rápido

### 1. Clonar el repositorio
```bash
git clone https://github.com/Bartolyna/geoespacial.git
cd geoespacial
```

### 2. Configurar variables de entorno
```bash
# Copiar archivo de configuración
cp .env.example.llm .env

# Editar .env con tus claves API reales
notepad .env
```

**⚠️ IMPORTANTE - Configurar claves API:**
```bash
# En el archivo .env, reemplazar con tus claves reales:
OPENAI_API_KEY=tu-clave-openai-aqui
ANTHROPIC_API_KEY=tu-clave-anthropic-aqui
LLM_DEFAULT_PROVIDER=openai  # o 'anthropic' o 'simulation'
```

### 3. Iniciar el sistema
```bash
docker-compose up -d
```

### 4. Ejecutar migraciones y seeders
```bash
docker-compose exec app php artisan migrate
docker-compose exec app php artisan db:seed
```

### 5. Abrir en navegador
**http://localhost:8080/**

¡Listo! El sistema está funcionando con IA integrada.

## ✅ Verificar que funciona

### Sistema Base
- ✅ Ver mapa con ubicaciones
- ✅ Temperaturas se actualizan solas cada 60 segundos
- ✅ Agregar nuevas ubicaciones (aparecen al instante)
- ✅ Ver cuántos usuarios están conectados
- ✅ **Búsquedas geoespaciales avanzadas con PostGIS**

### Nuevas Funcionalidades con IA
- ✅ **Dashboard de Reportes LLM**: http://localhost:8080/reports
- ✅ **Generar reportes meteorológicos con IA**
- ✅ **Análisis predictivo automatizado**
- ✅ **Herramientas de analytics**: http://localhost:8080/tools/analytics
- ✅ **Mapas interactivos**: http://localhost:8080/tools/maps
- ✅ **Datos meteorológicos**: http://localhost:8080/tools/weather

## 🤖 Sistema de Reportes con IA

### Probar la IA
```bash
# Generar reporte meteorológico con OpenAI
docker-compose exec app php artisan reports:generate weather --provider=openai --location=1

# Generar reporte con Anthropic Claude
docker-compose exec app php artisan reports:generate performance --provider=anthropic

# Generar reporte de simulación (gratuito)
docker-compose exec app php artisan reports:generate general --provider=simulation
```

### Tipos de Reportes Disponibles
- **general**: Análisis comprensivo del sistema
- **weather**: Análisis meteorológico detallado
- **spatial**: Evaluación geoespacial
- **performance**: Métricas de rendimiento
- **environmental**: Impacto ambiental
- **predictive**: Análisis predictivo y forecasting

### API de Reportes
```bash
# Listar todos los reportes
curl "http://localhost:8080/api/technical-reports" | ConvertFrom-Json

# Generar nuevo reporte
curl -X POST "http://localhost:8080/api/technical-reports/generate" -H "Content-Type: application/json" -d '{"type":"weather","provider":"openai","location_id":1}'

# Obtener estadísticas
curl "http://localhost:8080/api/technical-reports/stats" | ConvertFrom-Json
```

## 🌐 Rutas y Endpoints Disponibles

### Interfaces Web
- **Dashboard Principal**: http://localhost:8080/
- **Reportes LLM**: http://localhost:8080/reports
- **Crear Reporte**: http://localhost:8080/reports/create
- **Analytics**: http://localhost:8080/tools/analytics
- **Mapas Interactivos**: http://localhost:8080/tools/maps
- **Datos Meteorológicos**: http://localhost:8080/tools/weather

### API Endpoints

#### Sistema Base
```bash
GET  /api/geospatial/locations          # Listar ubicaciones
POST /api/geospatial/locations          # Crear ubicación
GET  /api/geospatial/summary            # Resumen meteorológico
GET  /api/postgis/info                  # Información PostGIS
```

#### Reportes IA
```bash
GET  /api/technical-reports             # Listar reportes
POST /api/technical-reports/generate    # Generar reporte
GET  /api/technical-reports/{id}        # Ver reporte específico
GET  /api/technical-reports/stats       # Estadísticas
DELETE /api/technical-reports/{id}      # Eliminar reporte
```

### 🗺️ PostGIS - Funcionalidades Geoespaciales

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

#### Problemas del Sistema Base
```bash
docker-compose restart worker
docker-compose restart queue-worker
docker-compose restart reverb
```

#### Problemas con IA
```bash
# Verificar configuración IA
docker-compose exec app php artisan config:show services.openai
docker-compose exec app php artisan config:show services.anthropic

# Probar conectividad con APIs
docker-compose exec app php artisan reports:generate general --provider=simulation

# Si OpenAI no funciona, usar Anthropic temporalmente
LLM_DEFAULT_PROVIDER=anthropic

# Si ambos fallan, usar simulación
LLM_DEFAULT_PROVIDER=simulation
```

#### Mensajes de Error Comunes

**"API key not provided"**
- ✅ Verificar que las claves están en `.env`
- ✅ Reiniciar contenedores: `docker-compose restart`

**"Rate limit exceeded"**
- ✅ Esperar unos minutos
- ✅ Cambiar a simulación: `LLM_DEFAULT_PROVIDER=simulation`

**"Model not found"**
- ✅ Verificar modelo en `.env`: `OPENAI_MODEL=gpt-3.5-turbo`

**"Connection timeout"**
- ✅ Verificar conexión a internet
- ✅ Usar simulación como respaldo

## 📋 Características Completas

### Core del Sistema
- 🌡️ Datos meteorológicos en tiempo real
- 🗺️ Mapa interactivo con PostGIS
- 👥 Monitor de usuarios conectados
- 🔄 Actualizaciones automáticas
- 📱 Responsive design (móviles)
- 🛡️ Sistema de seguridad completo

### Inteligencia Artificial (NUEVO)
- 🤖 **Integración con OpenAI GPT** (gpt-3.5-turbo, gpt-4)
- 🧠 **Integración con Anthropic Claude** (claude-3-haiku)
- 📊 **6 tipos de reportes automatizados**
- 💾 **Sistema de caché inteligente**
- 📈 **Analytics avanzados con métricas**
- 🗺️ **Mapas interactivos mejorados**
- 📄 **Exportación a PDF, JSON**

### Herramientas Adicionales
- 📊 **Analytics Dashboard** con estadísticas en tiempo real
- 🗺️ **Mapas Interactivos** con capas personalizables
- �️ **Monitor Meteorológico** con filtros avanzados
- 📋 **Sistema de reportes** con múltiples proveedores de IA

## ⚙️ Configuración Avanzada

### Variables de Entorno Importantes
```bash
# Proveedor IA por defecto
LLM_DEFAULT_PROVIDER=openai              # openai, anthropic, simulation

# Configuración de caché
LLM_CACHE_ENABLED=true
LLM_CACHE_DURATION=24                    # horas
LLM_RATE_LIMIT=30                        # requests por minuto

# OpenAI
OPENAI_API_KEY=tu-clave-aqui
OPENAI_MODEL=gpt-3.5-turbo              # o gpt-4
OPENAI_MAX_TOKENS=2048
OPENAI_TEMPERATURE=0.7

# Anthropic
ANTHROPIC_API_KEY=tu-clave-aqui  
ANTHROPIC_MODEL=claude-3-haiku-20240307
ANTHROPIC_MAX_TOKENS=2048

# Simulación (para pruebas sin costo)
LLM_SIMULATION_ENABLED=true
```

### Obtener Claves API

#### OpenAI
1. Visita: https://platform.openai.com/api-keys
2. Crea una cuenta/inicia sesión
3. Genera una nueva clave API
4. Copia la clave a tu archivo `.env`

#### Anthropic
1. Visita: https://console.anthropic.com/
2. Crea una cuenta/inicia sesión
3. Ve a "API Keys"
4. Genera una nueva clave
5. Copia la clave a tu archivo `.env`

### Comandos de Mantenimiento
```bash
# Limpiar caché de reportes
docker-compose exec app php artisan cache:clear

# Regenerar configuración
docker-compose exec app php artisan config:clear

# Ver logs de IA
docker-compose exec app tail -f storage/logs/laravel.log | grep -i "llm\|openai\|anthropic"

# Verificar estado de IA
docker-compose exec app php artisan tinker
>>> \App\Services\LLMService::checkProviderHealth('openai')
>>> \App\Services\LLMService::checkProviderHealth('anthropic')
```

## 🎯 Estado: ✅ SISTEMA COMPLETO CON IA

**Última actualización:** 1 de septiembre de 2025
- ✅ Sistema base completamente funcional
- ✅ Integración IA OpenAI + Anthropic
- ✅ 6 ciudades con datos meteorológicos optimizados
- ✅ Interfaz web moderna y responsive
- ✅ Documentación técnica completa
- ✅ Código limpio y escalable
