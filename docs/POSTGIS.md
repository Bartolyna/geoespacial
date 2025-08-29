# PostGIS Integration Documentation

## Descripción General

La aplicación geoespacial ahora incluye **PostGIS**, una extensión espacial de PostgreSQL que proporciona capacidades avanzadas de análisis geoespacial. Esta integración permite realizar consultas espaciales complejas, cálculos de distancia eficientes y análisis geográficos avanzados.

## Características Implementadas

### 🗃️ Base de Datos
- **PostgreSQL 13** con **PostGIS 3.1.4**
- Columna `geometry` en la tabla `locations` con índice espacial
- Triggers automáticos para sincronizar lat/lng con geometría
- Soporte completo para operaciones espaciales

### 🔧 Servicios

#### PostGISService
Servicio principal que proporciona:
- Información de versiones PostGIS
- Búsquedas por radio
- Cálculos de distancia
- Búsquedas de ubicaciones más cercanas
- Consultas dentro de polígonos
- Creación de buffers
- Estadísticas geográficas

### 🌐 API Endpoints

Todas las rutas están bajo el prefijo `/api/postgis/`:

#### Información del Sistema
```
GET /api/postgis/info
```
**Respuesta:**
```json
{
    "status": "success",
    "data": {
        "available": true,
        "version": "3.1 USE_GEOS=1 USE_PROJ=1 USE_STATS=1",
        "lib_version": "3.1.4",
        "extensions": {
            "postgis": "3.1.4"
        }
    }
}
```

#### Estadísticas Geográficas
```
GET /api/postgis/stats
```
**Respuesta:**
```json
{
    "status": "success",
    "data": {
        "total_locations": 6,
        "bounding_box": "BOX(-76.532 3.4516,9.19 45.4642)",
        "center_point": {
            "latitude": 23.5001,
            "longitude": -49.117517
        }
    }
}
```

#### Búsqueda por Radio
```
POST /api/postgis/search/radius
Content-Type: application/json

{
    "latitude": 40.7128,
    "longitude": -74.0060,
    "radius": 50000
}
```
**Parámetros:**
- `latitude`: Latitud del centro de búsqueda (-90 a 90)
- `longitude`: Longitud del centro de búsqueda (-180 a 180)
- `radius`: Radio en metros (100 a 100,000)

#### Ubicaciones Más Cercanas
```
POST /api/postgis/search/nearest
Content-Type: application/json

{
    "latitude": 40.7128,
    "longitude": -74.0060,
    "limit": 5
}
```

#### Cálculo de Distancia
```
POST /api/postgis/distance
Content-Type: application/json

{
    "from_latitude": 40.7128,
    "from_longitude": -74.0060,
    "to_latitude": 34.0522,
    "to_longitude": -118.2437
}
```

#### Búsqueda en Polígono
```
POST /api/postgis/search/polygon
Content-Type: application/json

{
    "coordinates": [
        {"lat": 40.7, "lng": -74.0},
        {"lat": 40.8, "lng": -74.0},
        {"lat": 40.8, "lng": -73.9},
        {"lat": 40.7, "lng": -73.9}
    ]
}
```

#### Centro Geográfico
```
GET /api/postgis/centroid
```

#### Crear Buffer
```
POST /api/postgis/locations/{location_id}/buffer
Content-Type: application/json

{
    "radius": 5000
}
```

## 🔒 Seguridad

### Rate Limiting
- **Consultas generales**: 60 requests/minuto
- **Operaciones intensivas** (buffers): 5 requests/minuto

### Validación
- Validación estricta de coordenadas geográficas
- Sanitización automática de entradas
- Logging de todas las operaciones PostGIS

## 🐳 Configuración Docker

### docker-compose.yml
```yaml
db:
  image: postgis/postgis:13-3.1-alpine
  environment:
    POSTGRES_DB: geoespacial_db
    POSTGRES_USER: geoespacial_user
    POSTGRES_PASSWORD: tu_password_aqui
  volumes:
    - ./database/init:/docker-entrypoint-initdb.d
    - postgres_data:/var/lib/postgresql/data
```

### Script de Inicialización
`database/init/01-enable-postgis.sh` habilita automáticamente:
- Extensión PostGIS
- PostGIS Topology
- PostGIS Raster

## 📊 Configuración de Base de Datos

### config/database.php
Optimizado para PostGIS con:
```php
'pgsql' => [
    'options' => [
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_STRINGIFY_FETCHES => false,
    ],
]
```

## 🏗️ Modelos

### Location Model
Métodos espaciales agregados:
- `scopeWithinDistance()`: Búsqueda por distancia
- `scopeOrderByDistance()`: Ordenar por distancia
- `getDistanceFromAttribute()`: Calcular distancia desde un punto

## 📈 Casos de Uso

### 1. Búsqueda de Ubicaciones Cercanas
```javascript
// Buscar tiendas en un radio de 5km
fetch('/api/postgis/search/radius', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
        latitude: user_lat,
        longitude: user_lng,
        radius: 5000
    })
})
```

### 2. Análisis de Cobertura
```javascript
// Crear área de cobertura de 10km alrededor de una ubicación
fetch('/api/postgis/locations/1/buffer', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
        radius: 10000
    })
})
```

### 3. Análisis de Área
```javascript
// Encontrar ubicaciones dentro de un área específica
fetch('/api/postgis/search/polygon', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
        coordinates: polygon_coordinates
    })
})
```

## 🚀 Despliegue

### 1. Iniciar Contenedores
```bash
docker-compose down
docker-compose up -d
```

### 2. Ejecutar Migraciones
```bash
docker-compose exec app php artisan migrate
```

### 3. Verificar PostGIS
```bash
curl http://localhost:8080/api/postgis/info
```

## 🧪 Testing

### Ejemplo de Prueba
```bash
# Información PostGIS
curl http://localhost:8080/api/postgis/info

# Búsqueda por radio (50km alrededor de NYC)
curl -X POST http://localhost:8080/api/postgis/search/radius \
  -H "Content-Type: application/json" \
  -d '{"latitude": 40.7128, "longitude": -74.0060, "radius": 50000}'

# Estadísticas geográficas
curl http://localhost:8080/api/postgis/stats
```

## 🔧 Rendimiento

### Optimizaciones Implementadas
- **Índices espaciales** en columna geometry
- **Triggers automáticos** para mantener sincronización
- **Conexión persistente** a PostgreSQL
- **Rate limiting** para prevenir sobrecarga

### Métricas de Rendimiento
- Consultas espaciales sub-segundo en datasets < 10,000 puntos
- Índice GIST para búsquedas espaciales eficientes
- Soporte para consultas concurrentes

## 🎯 Próximos Pasos

### Funcionalidades Planificadas
1. **Clustering espacial** para grandes datasets
2. **Análisis de densidad** de puntos
3. **Rutas optimizadas** entre ubicaciones
4. **Importación masiva** de datos geoespaciales
5. **Visualización avanzada** con mapas

### Integraciones Futuras
- Google Maps API
- OpenStreetMap
- Servicios de geocodificación
- APIs de routing

---

**Estado**: ✅ **Completamente Funcional**
**Versión PostGIS**: 3.1.4
**Fecha**: Agosto 2025
