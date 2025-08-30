# 📊 Consultas SQL PostGIS - Análisis Espacial Avanzado

Este documento contiene consultas SQL de ejemplo para análisis espacial avanzado usando PostGIS.

## 🎯 1. Eventos dentro de un Radio Dado

### Consulta Básica - Ubicaciones con Datos Meteorológicos
```sql
-- Buscar ubicaciones con datos meteorológicos recientes dentro de 10km de un punto
SELECT 
    l.id,
    l.name,
    l.city,
    l.country,
    l.latitude,
    l.longitude,
    wd.temperature,
    wd.humidity,
    wd.pressure,
    wd.wind_speed,
    wd.dt as weather_timestamp,
    ST_Distance(
        l.geom, 
        ST_SetSRID(ST_MakePoint(-74.0060, 40.7128), 4326)
    ) as distance_meters,
    ROUND(
        ST_Distance(
            l.geom, 
            ST_SetSRID(ST_MakePoint(-74.0060, 40.7128), 4326)
        )::numeric, 2
    ) as distance_rounded
FROM locations l
INNER JOIN weather_data wd ON l.id = wd.location_id
WHERE l.active = true
AND ST_DWithin(
    l.geom, 
    ST_SetSRID(ST_MakePoint(-74.0060, 40.7128), 4326), 
    10000  -- 10km radius
)
AND wd.id = (
    -- Solo el dato meteorológico más reciente por ubicación
    SELECT id FROM weather_data wd2 
    WHERE wd2.location_id = l.id 
    ORDER BY wd2.dt DESC 
    LIMIT 1
)
ORDER BY distance_meters ASC;
```

### Consulta Avanzada - Eventos con Clasificación por Distancia
```sql
-- Eventos clasificados por zona de distancia
WITH events_by_zone AS (
    SELECT 
        l.*,
        wd.temperature,
        wd.humidity,
        ST_Distance(
            l.geom, 
            ST_SetSRID(ST_MakePoint(-74.0060, 40.7128), 4326)
        ) as distance_meters,
        CASE 
            WHEN ST_Distance(l.geom, ST_SetSRID(ST_MakePoint(-74.0060, 40.7128), 4326)) <= 2000 THEN 'Zona Inmediata'
            WHEN ST_Distance(l.geom, ST_SetSRID(ST_MakePoint(-74.0060, 40.7128), 4326)) <= 5000 THEN 'Zona Cercana'
            WHEN ST_Distance(l.geom, ST_SetSRID(ST_MakePoint(-74.0060, 40.7128), 4326)) <= 10000 THEN 'Zona Extendida'
            ELSE 'Zona Lejana'
        END as proximity_zone
    FROM locations l
    INNER JOIN weather_data wd ON l.id = wd.location_id
    WHERE l.active = true
    AND ST_DWithin(l.geom, ST_SetSRID(ST_MakePoint(-74.0060, 40.7128), 4326), 15000)
    AND wd.id = (SELECT id FROM weather_data wd2 WHERE wd2.location_id = l.id ORDER BY wd2.dt DESC LIMIT 1)
)
SELECT 
    proximity_zone,
    COUNT(*) as event_count,
    AVG(temperature) as avg_temperature,
    AVG(humidity) as avg_humidity,
    AVG(distance_meters) as avg_distance,
    STRING_AGG(name, ', ') as locations
FROM events_by_zone
GROUP BY proximity_zone
ORDER BY avg_distance;
```

## 🔍 2. Clustering usando ST_ClusterDBSCAN

### Clustering Básico
```sql
-- Agrupar ubicaciones en clusters basados en proximidad espacial
WITH clustered_locations AS (
    SELECT 
        l.*,
        ST_ClusterDBSCAN(l.geom, 1000, 2) OVER() as cluster_id
    FROM locations l
    WHERE l.active = true
)
SELECT 
    cluster_id,
    COUNT(*) as total_points,
    AVG(latitude) as avg_latitude,
    AVG(longitude) as avg_longitude,
    STRING_AGG(name, ', ') as cluster_locations,
    STRING_AGG(DISTINCT country, ', ') as countries_in_cluster
FROM clustered_locations
WHERE cluster_id IS NOT NULL  -- Excluir outliers
GROUP BY cluster_id
ORDER BY cluster_id;
```

### Clustering Avanzado con Estadísticas Geométricas
```sql
-- Clustering con estadísticas geométricas completas
WITH clustered_locations AS (
    SELECT 
        l.*,
        ST_ClusterDBSCAN(l.geom, 2000, 2) OVER() as cluster_id
    FROM locations l
    WHERE l.active = true
),
cluster_stats AS (
    SELECT 
        cluster_id,
        COUNT(*) as total_points,
        ST_Centroid(ST_Collect(geom)) as cluster_center,
        ST_ConvexHull(ST_Collect(geom)) as cluster_hull,
        ST_Area(ST_ConvexHull(ST_Collect(geom))) as cluster_area,
        MIN(name) as sample_location,
        AVG(latitude) as center_lat,
        AVG(longitude) as center_lng,
        STDDEV(ST_X(geom)) as lng_variance,
        STDDEV(ST_Y(geom)) as lat_variance
    FROM clustered_locations
    WHERE cluster_id IS NOT NULL
    GROUP BY cluster_id
)
SELECT 
    cl.*,
    cs.total_points as cluster_total,
    ST_X(cs.cluster_center) as cluster_center_lng,
    ST_Y(cs.cluster_center) as cluster_center_lat,
    cs.cluster_area as cluster_area_sq_degrees,
    cs.sample_location as cluster_sample,
    cs.lng_variance,
    cs.lat_variance,
    CASE 
        WHEN cl.cluster_id IS NULL THEN 'Outlier'
        ELSE CONCAT('Cluster ', cl.cluster_id)
    END as cluster_label,
    CASE 
        WHEN cs.total_points >= 5 THEN 'Cluster Grande'
        WHEN cs.total_points >= 3 THEN 'Cluster Mediano'
        ELSE 'Cluster Pequeño'
    END as cluster_size_category
FROM clustered_locations cl
LEFT JOIN cluster_stats cs ON cl.cluster_id = cs.cluster_id
ORDER BY cl.cluster_id NULLS LAST, cl.name;
```


## 📈 3. Análisis de Densidad con ST_DWithin

### Análisis de Densidad Básico
```sql
-- Calcular densidad de ubicaciones en un radio de 5km
SELECT 
    l1.id,
    l1.name,
    l1.city,
    l1.country,
    l1.latitude,
    l1.longitude,
    COUNT(l2.id) - 1 as neighbors_count,  -- Excluir la ubicación misma
    ROUND(
        (COUNT(l2.id) - 1) / (PI() * POWER(5, 2))::numeric, 4
    ) as density_per_km2,
    ARRAY_AGG(
        DISTINCT l2.name ORDER BY l2.name
    ) FILTER (WHERE l2.id != l1.id) as neighbor_names
FROM locations l1
CROSS JOIN locations l2
WHERE l1.active = true 
AND l2.active = true
AND ST_DWithin(l1.geom, l2.geom, 5000)  -- 5km radius
GROUP BY l1.id, l1.name, l1.city, l1.country, l1.latitude, l1.longitude
ORDER BY neighbors_count DESC;
```

### Análisis de Densidad con Estadísticas Z-Score
```sql
-- Análisis de densidad con normalización estadística
WITH density_analysis AS (
    SELECT 
        l1.id,
        l1.name,
        l1.city,
        l1.country,
        l1.latitude,
        l1.longitude,
        COUNT(l2.id) - 1 as neighbors_count,
        ROUND(
            (COUNT(l2.id) - 1) / (PI() * POWER(5, 2))::numeric, 4
        ) as density_per_km2,
        AVG(
            ST_Distance(l1.geom, l2.geom)
        ) FILTER (WHERE l2.id != l1.id) as avg_distance_to_neighbors
    FROM locations l1
    CROSS JOIN locations l2
    WHERE l1.active = true 
    AND l2.active = true
    AND ST_DWithin(l1.geom, l2.geom, 5000)
    GROUP BY l1.id, l1.name, l1.city, l1.country, l1.latitude, l1.longitude
),
density_stats AS (
    SELECT 
        AVG(density_per_km2) as avg_density,
        STDDEV(density_per_km2) as stddev_density,
        MIN(density_per_km2) as min_density,
        MAX(density_per_km2) as max_density,
        PERCENTILE_CONT(0.5) WITHIN GROUP (ORDER BY density_per_km2) as median_density,
        PERCENTILE_CONT(0.25) WITHIN GROUP (ORDER BY density_per_km2) as q1_density,
        PERCENTILE_CONT(0.75) WITHIN GROUP (ORDER BY density_per_km2) as q3_density
    FROM density_analysis
)
SELECT 
    da.*,
    ds.avg_density,
    ds.stddev_density,
    ds.median_density,
    CASE 
        WHEN da.density_per_km2 > ds.avg_density + ds.stddev_density THEN 'Alta Densidad'
        WHEN da.density_per_km2 < ds.avg_density - ds.stddev_density THEN 'Baja Densidad'
        ELSE 'Densidad Normal'
    END as density_category,
    ROUND(
        ((da.density_per_km2 - ds.avg_density) / NULLIF(ds.stddev_density, 0))::numeric, 2
    ) as z_score,
    ROUND(
        (da.density_per_km2 / ds.avg_density)::numeric, 2
    ) as density_ratio
FROM density_analysis da
CROSS JOIN density_stats ds
ORDER BY da.density_per_km2 DESC;
```

### Análisis de Densidad por Regiones
```sql
-- Análisis de densidad agrupado por país/región
WITH regional_density AS (
    SELECT 
        l1.country,
        l1.city,
        COUNT(DISTINCT l1.id) as total_locations,
        AVG(
            (SELECT COUNT(*) - 1 
             FROM locations l3 
             WHERE l3.active = true 
             AND ST_DWithin(l1.geom, l3.geom, 10000))
        ) as avg_neighbors_per_location,
        AVG(
            (SELECT AVG(ST_Distance(l1.geom, l4.geom))
             FROM locations l4 
             WHERE l4.active = true AND l4.id != l1.id
             AND ST_DWithin(l1.geom, l4.geom, 10000))
        ) as avg_neighbor_distance,
        ST_Extent(l1.geom) as region_bounds
    FROM locations l1
    WHERE l1.active = true
    GROUP BY l1.country, l1.city
)
SELECT 
    country,
    city,
    total_locations,
    ROUND(avg_neighbors_per_location::numeric, 2) as avg_neighbors,
    ROUND(avg_neighbor_distance::numeric, 0) as avg_distance_meters,
    CASE 
        WHEN avg_neighbors_per_location >= 3 THEN 'Alta Concentración'
        WHEN avg_neighbors_per_location >= 1 THEN 'Concentración Media'
        ELSE 'Baja Concentración'
    END as concentration_level,
    region_bounds
FROM regional_density
ORDER BY avg_neighbors_per_location DESC;
```

## 🔄 4. Consulta Combinada - Análisis Espacial Integral

### Análisis Comprensivo
```sql
-- Análisis espacial que combina eventos, clustering y densidad
-- Análisis espacial que combina eventos, clustering y densidad
WITH search_area AS (
    SELECT ST_SetSRID(ST_MakePoint(-74.0060, 40.7128), 4326) as center_point,
           15000 as search_radius
),
events_in_area AS (
    SELECT l.id, l.name, l.city, l.country, l.latitude, l.longitude, l.geom,
           wd.temperature, wd.humidity,
           ST_Distance(l.geom, sa.center_point) as distance_from_center
    FROM locations l
    INNER JOIN weather_data wd ON l.id = wd.location_id
    CROSS JOIN search_area sa
    WHERE l.active = true
    AND ST_DWithin(l.geom, sa.center_point, sa.search_radius)
    AND wd.id = (SELECT id FROM weather_data wd2 WHERE wd2.location_id = l.id ORDER BY wd2.dt DESC LIMIT 1)
),
clustered_events AS (
    SELECT *,
           ST_ClusterDBSCAN(geom, 2000, 2) OVER() as cluster_id
    FROM events_in_area
),
density_analysis AS (
    SELECT e1.id,
           COUNT(e2.id) - 1 as local_density
    FROM events_in_area e1
    CROSS JOIN events_in_area e2
    WHERE ST_DWithin(e1.geom, e2.geom, 5000)
    GROUP BY e1.id
)
SELECT 
    ce.name,
    ce.city,
    ce.country,
    ce.temperature,
    ce.humidity,
    ROUND(ce.distance_from_center::numeric, 0) as distance_meters,
    COALESCE(ce.cluster_id, -1) as cluster_id,
    da.local_density,
    CASE 
        WHEN ce.cluster_id IS NOT NULL THEN 'Agrupado'
        ELSE 'Aislado'
    END as clustering_status,
    CASE 
        WHEN da.local_density >= 3 THEN 'Alta Densidad Local'
        WHEN da.local_density >= 1 THEN 'Densidad Media'
        ELSE 'Baja Densidad Local'
    END as density_status
FROM clustered_events ce
LEFT JOIN density_analysis da ON ce.id = da.id
ORDER BY ce.distance_from_center;
```



---

## 📊 Funciones PostGIS Utilizadas

### Funciones Espaciales Principales
- **ST_Distance()**: Calcular distancias geodésicas
- **ST_DWithin()**: Búsquedas eficientes por radio
- **ST_ClusterDBSCAN()**: Clustering espacial automático
- **ST_MakePoint()**: Crear puntos desde coordenadas
- **ST_SetSRID()**: Establecer sistema de referencia espacial
- **ST_Centroid()**: Centro geométrico de geometrías
- **ST_ConvexHull()**: Envolvente convexa de un conjunto de puntos
- **ST_Collect()**: Agrupar geometrías
- **ST_Area()**: Calcular área de polígonos
- **ST_Extent()**: Caja delimitadora de geometrías

### Técnicas Avanzadas Utilizadas
- **Common Table Expressions (CTE)**: Para consultas complejas estructuradas
- **Window Functions**: Para clustering y análisis por particiones
- **Agregaciones Espaciales**: STRING_AGG, ARRAY_AGG para resultados agrupados
- **Análisis Estadístico**: STDDEV, PERCENTILE_CONT, CORR para métricas
- **Clasificación Condicional**: CASE WHEN para categorización automática

**Estas consultas proporcionan la base completa para análisis espacial avanzado con PostGIS** 🚀
