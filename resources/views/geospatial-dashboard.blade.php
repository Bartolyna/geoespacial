<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Geoespacial - Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">
                🌍 Sistema Geoespacial en Tiempo Real
            </h1>
            <p class="text-gray-600">
                Monitoreo meteorológico con WebSockets y OpenWeather API
            </p>
            <div class="flex items-center mt-4">
                <div id="connection-status" class="flex items-center">
                    <div class="w-3 h-3 bg-red-500 rounded-full mr-2"></div>
                    <span class="text-sm">Desconectado</span>
                </div>
                <div class="ml-6">
                    <span class="text-sm text-gray-500">Última actualización: </span>
                    <span id="last-update" class="text-sm font-medium">Nunca</span>
                </div>
            </div>
        </div>

        <!-- Estadísticas -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-blue-100 rounded-md">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Ubicaciones</p>
                        <p id="total-locations" class="text-2xl font-semibold text-gray-900">0</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-green-100 rounded-md">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Activas</p>
                        <p id="active-locations" class="text-2xl font-semibold text-gray-900">0</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-yellow-100 rounded-md">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Alertas</p>
                        <p id="active-alerts" class="text-2xl font-semibold text-gray-900">0</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-purple-100 rounded-md">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Actualizaciones</p>
                        <p id="updates-count" class="text-2xl font-semibold text-gray-900">0</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mapa y Lista -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Mapa -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Mapa de Ubicaciones</h2>
                <div id="map" class="h-96 rounded-lg"></div>
            </div>

            <!-- Lista de ubicaciones -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Ubicaciones en Tiempo Real</h2>
                <div id="locations-list" class="space-y-4 max-h-96 overflow-y-auto">
                    <!-- Las ubicaciones se cargarán aquí dinámicamente -->
                </div>
            </div>
        </div>

        <!-- Formulario para agregar ubicación -->
        <div class="bg-white rounded-lg shadow-lg p-6 mt-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Agregar Nueva Ubicación</h2>
            <form id="add-location-form" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nombre</label>
                    <input type="text" name="name" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Ciudad</label>
                    <input type="text" name="city" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">País</label>
                    <input type="text" name="country" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Latitud</label>
                    <input type="number" name="latitude" step="0.000001" min="-90" max="90" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Longitud</label>
                    <input type="number" name="longitude" step="0.000001" min="-180" max="180" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        Agregar Ubicación
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Log de actividades -->
    <div class="fixed bottom-4 right-4 w-80 bg-white rounded-lg shadow-lg p-4 max-h-64 overflow-y-auto" id="activity-log">
        <h3 class="text-sm font-semibold text-gray-800 mb-2">Log de Actividades</h3>
        <div id="log-entries" class="space-y-1 text-xs">
            <!-- Los logs se cargarán aquí -->
        </div>
    </div>

    <script>
        class GeospatialDashboard {
            constructor() {
                this.map = null;
                this.markers = {};
                this.pusher = null;
                this.channel = null;
                this.updatesCount = 0;
                this.init();
            }

            async init() {
                this.initMap();
                this.initWebSocket();
                this.initEventListeners();
                await this.loadLocations();
                this.log('Dashboard inicializado');
            }

            initMap() {
                this.map = L.map('map').setView([20.0, -20.0], 2); // Vista global para cubrir todas las ubicaciones
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors'
                }).addTo(this.map);
            }

            async initWebSocket() {
                try {
                    // Obtener configuración WebSocket
                    console.log('Obteniendo configuración WebSocket...');
                    const response = await fetch('/api/websocket/info');
                    
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    
                    const config = await response.json();
                    console.log('Configuración WebSocket:', config);

                    this.pusher = new Pusher(config.app_key, {
                        wsHost: config.reverb_host,
                        wsPort: config.reverb_port,
                        wssPort: config.reverb_port,
                        forceTLS: false,
                        enabledTransports: ['ws'],
                        disableStats: true,
                        cluster: 'mt1',
                        encrypted: false,
                        debug: true
                    });

                    this.channel = this.pusher.subscribe(config.channels.main);
                    console.log('Suscrito al canal:', config.channels.main);
                    
                    this.channel.bind('weather.updated', (data) => {
                        console.log('Evento weather.updated recibido:', data);
                        this.handleWeatherUpdate(data);
                    });

                    this.channel.bind('weather.summary', (data) => {
                        console.log('Evento weather.summary recibido:', data);
                        this.handleSummaryUpdate(data);
                    });

                    this.pusher.connection.bind('connected', () => {
                        console.log('WebSocket conectado exitosamente');
                        this.updateConnectionStatus(true);
                        this.log('Conectado a WebSocket');
                    });

                    this.pusher.connection.bind('disconnected', () => {
                        console.log('WebSocket desconectado');
                        this.updateConnectionStatus(false);
                        this.log('Desconectado de WebSocket');
                    });

                    this.pusher.connection.bind('error', (error) => {
                        console.error('Error de WebSocket:', error);
                        this.log('Error WebSocket: ' + error.message);
                    });

                } catch (error) {
                    console.error('Error inicializando WebSocket:', error);
                    this.log('Error conectando WebSocket: ' + error.message);
                }
            }

            initEventListeners() {
                document.getElementById('add-location-form').addEventListener('submit', (e) => {
                    e.preventDefault();
                    this.addLocation(e.target);
                });
            }

            async loadLocations() {
                try {
                    const response = await fetch('/api/geospatial/locations');
                    const result = await response.json();
                    
                    if (result.status === 'success') {
                        this.renderLocations(result.data);
                        this.updateStats({ total_locations: result.total });
                    }
                } catch (error) {
                    console.error('Error cargando ubicaciones:', error);
                    this.log('Error cargando ubicaciones: ' + error.message);
                }
            }

            async addLocation(form) {
                const formData = new FormData(form);
                const data = Object.fromEntries(formData);
                
                try {
                    const response = await fetch('/api/geospatial/locations', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                        },
                        body: JSON.stringify(data)
                    });

                    const result = await response.json();
                    
                    if (result.status === 'success') {
                        this.log(`Ubicación agregada: ${data.name}`);
                        form.reset();
                        await this.loadLocations();
                    } else {
                        this.log('Error agregando ubicación: ' + result.message);
                    }
                } catch (error) {
                    console.error('Error agregando ubicación:', error);
                    this.log('Error agregando ubicación: ' + error.message);
                }
            }

            renderLocations(locations) {
                const container = document.getElementById('locations-list');
                container.innerHTML = '';

                locations.forEach(location => {
                    this.addLocationToMap(location);
                    this.addLocationToList(location, container);
                });
            }

            addLocationToMap(location) {
                const marker = L.marker([location.latitude, location.longitude])
                    .addTo(this.map);

                const weatherData = location.weather_data?.[0];
                let popupContent = `<strong>${location.name}</strong><br>${location.city}, ${location.country}`;
                
                if (weatherData) {
                    popupContent += `<br>🌡️ ${weatherData.temperature}°C<br>💨 ${weatherData.wind_speed || 0} km/h`;
                }

                marker.bindPopup(popupContent);
                this.markers[location.id] = marker;
            }

            addLocationToList(location, container) {
                const weatherData = location.weather_data?.[0];
                
                const item = document.createElement('div');
                item.className = 'border border-gray-200 rounded-lg p-4';
                item.setAttribute('data-location-id', location.id);
                item.innerHTML = `
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="font-semibold text-gray-800">${location.name}</h3>
                            <p class="text-sm text-gray-600">${location.city}, ${location.country}</p>
                            ${weatherData ? `
                                <div class="mt-2 space-y-1">
                                    <div class="flex items-center text-sm">
                                        <span class="mr-2">🌡️</span>
                                        <span class="temperature">${weatherData.temperature}°C</span>
                                        <span class="weather-description ml-1">(${weatherData.weather_description})</span>
                                    </div>
                                    <div class="flex items-center text-sm">
                                        <span class="mr-2">💨</span>
                                        <span class="wind-speed">${weatherData.wind_speed || 0} km/h</span>
                                    </div>
                                    <div class="flex items-center text-sm">
                                        <span class="mr-2">💧</span>
                                        <span class="humidity">${weatherData.humidity}%</span>
                                    </div>
                                </div>
                            ` : '<p class="text-sm text-gray-500 mt-2">Sin datos meteorológicos</p>'}
                        </div>
                        <div class="text-right">
                            <span class="inline-block w-3 h-3 ${location.active ? 'bg-green-500' : 'bg-red-500'} rounded-full"></span>
                            <p class="text-xs text-gray-500 mt-1">${location.active ? 'Activa' : 'Inactiva'}</p>
                        </div>
                    </div>
                `;
                
                container.appendChild(item);
            }

            handleWeatherUpdate(data) {
                console.log('Evento weather.updated recibido:', data);
                this.updatesCount++;
                document.getElementById('updates-count').textContent = this.updatesCount;
                document.getElementById('last-update').textContent = new Date().toLocaleTimeString();
                
                // Actualizar marcador en mapa
                if (this.markers[data.location.id]) {
                    const marker = this.markers[data.location.id];
                    const popupContent = `
                        <strong>${data.location.name}</strong><br>
                        ${data.location.city}, ${data.location.country}<br>
                        🌡️ ${data.weather.temperature}°C (${data.weather.weather_description})<br>
                        🌡️ Min: ${data.weather.temp_min}°C / Max: ${data.weather.temp_max}°C<br>
                        💨 ${data.weather.wind_speed || 0} km/h<br>
                        💧 ${data.weather.humidity}%<br>
                        📊 ${data.weather.pressure} hPa
                    `;
                    marker.setPopupContent(popupContent);
                }

                // Actualizar la tarjeta de ubicación en la lista
                this.updateLocationCard(data.location.id, data);
                
                this.log(`${data.location.name}: ${data.weather.temperature}°C - ${data.weather.weather_description}`);
            }

            handleSummaryUpdate(data) {
                console.log('Evento weather.summary recibido:', data);
                
                // Actualizar estadísticas generales
                this.updateStats({
                    total_locations: data.length,
                    active_locations: data.length,
                    active_alerts: data.filter(item => this.isAlert(item.weather)).length
                });
                
                // Actualizar todas las ubicaciones en el mapa y lista
                data.forEach(locationData => {
                    this.updateLocationCard(locationData.location.id, locationData);
                    
                    // Actualizar marcador si existe
                    if (this.markers[locationData.location.id]) {
                        const marker = this.markers[locationData.location.id];
                        const popupContent = `
                            <strong>${locationData.location.name}</strong><br>
                            ${locationData.location.city}, ${locationData.location.country}<br>
                            🌡️ ${locationData.weather.temperature}°C (${locationData.weather.weather_description})<br>
                            💨 ${locationData.weather.wind_speed || 0} km/h<br>
                            💧 ${locationData.weather.humidity}%
                        `;
                        marker.setPopupContent(popupContent);
                    }
                });
                
                this.log(`Resumen actualizado: ${data.length} ubicaciones`);
            }

            updateLocationCard(locationId, data) {
                // Buscar la tarjeta de ubicación por ID y actualizarla
                const locationElement = document.querySelector(`[data-location-id="${locationId}"]`);
                if (locationElement) {
                    // Actualizar temperatura
                    const tempElement = locationElement.querySelector('.temperature');
                    if (tempElement) {
                        tempElement.textContent = `${data.weather.temperature}°C`;
                    }
                    
                    // Actualizar descripción
                    const descElement = locationElement.querySelector('.weather-description');
                    if (descElement) {
                        descElement.textContent = data.weather.weather_description;
                    }
                    
                    // Actualizar otros datos
                    const windElement = locationElement.querySelector('.wind-speed');
                    if (windElement) {
                        windElement.textContent = `${data.weather.wind_speed || 0} km/h`;
                    }
                    
                    const humidityElement = locationElement.querySelector('.humidity');
                    if (humidityElement) {
                        humidityElement.textContent = `${data.weather.humidity}%`;
                    }
                }
            }

            isAlert(weather) {
                // Definir condiciones de alerta
                return weather.temperature > 35 || 
                       weather.temperature < 0 || 
                       (weather.wind_speed && weather.wind_speed > 50) ||
                       weather.weather_main === 'Thunderstorm' ||
                       weather.weather_main === 'Tornado';
            }

            updateConnectionStatus(connected) {
                const statusElement = document.getElementById('connection-status');
                const dot = statusElement.querySelector('div');
                const text = statusElement.querySelector('span');
                
                if (connected) {
                    dot.className = 'w-3 h-3 bg-green-500 rounded-full mr-2';
                    text.textContent = 'Conectado';
                } else {
                    dot.className = 'w-3 h-3 bg-red-500 rounded-full mr-2';
                    text.textContent = 'Desconectado';
                }
            }

            updateStats(stats) {
                if (stats.total_locations !== undefined) {
                    document.getElementById('total-locations').textContent = stats.total_locations;
                    document.getElementById('active-locations').textContent = stats.total_locations;
                }
                if (stats.active_alerts !== undefined) {
                    document.getElementById('active-alerts').textContent = stats.active_alerts;
                }
            }

            log(message) {
                const logContainer = document.getElementById('log-entries');
                const entry = document.createElement('div');
                entry.className = 'text-gray-600';
                entry.innerHTML = `<span class="text-gray-400">${new Date().toLocaleTimeString()}</span> ${message}`;
                
                logContainer.insertBefore(entry, logContainer.firstChild);
                
                // Mantener solo los últimos 20 logs
                while (logContainer.children.length > 20) {
                    logContainer.removeChild(logContainer.lastChild);
                }
            }
        }

        // Inicializar dashboard cuando se carga la página
        document.addEventListener('DOMContentLoaded', () => {
            new GeospatialDashboard();
        });
    </script>
</body>
</html>
