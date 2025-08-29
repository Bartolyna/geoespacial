#!/bin/bash
set -e

# Script de inicialización para habilitar PostGIS en PostgreSQL
# Se ejecuta automáticamente cuando se crea la base de datos

echo "🗺️ Habilitando extensiones PostGIS..."

# Conectar a la base de datos y habilitar extensiones
psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname "$POSTGRES_DB" <<-EOSQL
    -- Habilitar extensión PostGIS (geometrías espaciales)
    CREATE EXTENSION IF NOT EXISTS postgis;
    
    -- Habilitar extensión PostGIS Topology (opcional)
    CREATE EXTENSION IF NOT EXISTS postgis_topology;
    
    -- Habilitar extensión PostGIS Raster (opcional)
    CREATE EXTENSION IF NOT EXISTS postgis_raster;
    
    -- Habilitar funciones adicionales de PostGIS
    CREATE EXTENSION IF NOT EXISTS fuzzystrmatch;
    CREATE EXTENSION IF NOT EXISTS postgis_tiger_geocoder;
    
    -- Verificar versiones instaladas
    SELECT postgis_version();
    SELECT postgis_lib_version();
    
    -- Mostrar extensiones habilitadas
    SELECT name, default_version, installed_version 
    FROM pg_available_extensions 
    WHERE name LIKE 'postgis%' AND installed_version IS NOT NULL;
EOSQL

echo "✅ PostGIS habilitado correctamente!"
echo "📍 Extensiones disponibles: PostGIS, PostGIS Topology, PostGIS Raster"
echo "🔍 Funciones geoespaciales listas para usar"
