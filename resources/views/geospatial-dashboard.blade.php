<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
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
                <button id="reconnect-btn" class="ml-4 px-3 py-1 bg-blue-500 text-white text-xs rounded hover:bg-blue-600 transition-colors hidden" onclick="dashboard.manualReconnect()">
                    Reconectar
                </button>
                <button id="refresh-btn" class="ml-2 px-3 py-1 bg-green-500 text-white text-xs rounded hover:bg-green-600 transition-colors" onclick="dashboard.forceRefresh()">
                    Actualizar
                </button>
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Usuarios Activos</p>
                        <p id="websocket-connections" class="text-2xl font-semibold text-gray-900">
                            <span class="animate-pulse">...</span>
                        </p>
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
                this.pollingInterval = null;
                this.isUpdating = false; // Prevenir actualizaciones simultáneas
                
                // Configuración de reconexión
                this.reconnectAttempts = 0;
                this.maxReconnectAttempts = 5;
                this.exponentialBackoff = true;
                this.maxBackoffDelay = 60;
                this.baseReconnectDelay = 1;
                this.currentReconnectDelay = 1;
                this.reconnectTimer = null;
                this.heartbeatInterval = null;
                this.heartbeatTimer = null;
                
                // Identificador único para esta instancia/pestaña
                this.connectionId = this.generateConnectionId();
                this.sessionId = this.generateSessionId();
                this.tabId = this.generateTabId();
                
                this.connectionState = 'disconnected';
                this.lastPongReceived = null;
                this.isVisible = !document.hidden;
                this.lastActivity = Date.now();
                
                // Configurar manejo de visibilidad de página
                this.setupVisibilityHandling();
                
                this.init();
            }

            generateConnectionId() {
                return 'conn_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            }

            generateSessionId() {
                // Usar sessionStorage para que sea único por pestaña
                let sessionId = sessionStorage.getItem('geospatial_session_id');
                if (!sessionId) {
                    sessionId = 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
                    sessionStorage.setItem('geospatial_session_id', sessionId);
                }
                return sessionId;
            }

            generateTabId() {
                return 'tab_' + Date.now() + '_' + Math.random().toString(36).substr(2, 5);
            }

            setupVisibilityHandling() {
                // Manejar cambios de visibilidad de la página
                document.addEventListener('visibilitychange', () => {
                    this.isVisible = !document.hidden;
                    console.log(`[${this.connectionId}] Visibilidad cambiada:`, this.isVisible ? 'visible' : 'hidden');
                    
                    if (this.isVisible) {
                        console.log(`[${this.connectionId}] Pestaña activa, reactivando conexión...`);
                        this.onTabActive();
                    } else {
                        console.log(`[${this.connectionId}] Pestaña inactiva, reduciendo actividad...`);
                        this.onTabInactive();
                    }
                });

                // Manejar beforeunload para limpiar conexiones
                window.addEventListener('beforeunload', () => {
                    this.cleanup();
                });

                // Detectar actividad del usuario
                ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart'].forEach(event => {
                    document.addEventListener(event, () => {
                        this.lastActivity = Date.now();
                    }, { passive: true });
                });
            }

            onTabActive() {
                // Reactivar WebSocket si estaba desconectado
                if (this.connectionState !== 'connected' && this.connectionState !== 'connecting') {
                    console.log(`[${this.connectionId}] Reconectando por activación de pestaña...`);
                    this.initWebSocket().catch(error => {
                        console.warn(`[${this.connectionId}] Error reconectando:`, error);
                        this.startPolling();
                    });
                }
                
                // Cargar datos frescos
                setTimeout(() => {
                    this.loadLatestData();
                }, 500);
            }

            onTabInactive() {
                // No desconectar completamente, solo reducir actividad
                console.log(`[${this.connectionId}] Pestaña inactiva, manteniendo conexión pero reduciendo actividad`);
            }

            cleanup() {
                console.log(`[${this.connectionId}] Limpiando recursos...`);
                
                if (this.pusher) {
                    this.pusher.disconnect();
                }
                
                if (this.pollingInterval) {
                    clearInterval(this.pollingInterval);
                }
                
                if (this.reconnectTimer) {
                    clearTimeout(this.reconnectTimer);
                }
                
                if (this.heartbeatTimer) {
                    clearInterval(this.heartbeatTimer);
                }
                
                if (this.wsStatsInterval) {
                    clearInterval(this.wsStatsInterval);
                }
                
                if (this.connectionHeartbeat) {
                    clearInterval(this.connectionHeartbeat);
                }
            }

            async init() {
                console.log(`[${this.connectionId}] Iniciando dashboard...`);
                
                // Inicializar componentes básicos primero
                this.initMap();
                this.initEventListeners();
                
                // Cargar datos iniciales una sola vez
                await this.loadLatestData();
                
                // Actualizar estadísticas WebSocket inicialmente
                await this.updateWebSocketStats();
                
                // Iniciar heartbeat para registrar esta conexión
                this.startConnectionHeartbeat();
                
                // Programar actualizaciones periódicas de estadísticas WebSocket cada 10 segundos
                this.wsStatsInterval = setInterval(() => {
                    this.updateWebSocketStats();
                }, 10000);
                
                // Intentar WebSocket como método principal
                try {
                    await this.initWebSocket();
                    console.log(`[${this.connectionId}] WebSocket conectado - actualizaciones en tiempo real activas`);
                    this.log('WebSocket conectado - actualizaciones en tiempo real activas');
                } catch (error) {
                    console.warn(`[${this.connectionId}] WebSocket falló, activando polling como respaldo:`, error);
                    this.log('WebSocket no disponible, usando polling como respaldo');
                    this.startPolling();
                }
                
                this.log('Dashboard inicializado');
                console.log(`[${this.connectionId}] Dashboard inicializado correctamente`);
            }

            initMap() {
                this.map = L.map('map').setView([20.0, -20.0], 2); // Vista global para cubrir todas las ubicaciones
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors'
                }).addTo(this.map);
            }

            async initWebSocket() {
                try {
                    this.connectionState = 'connecting';
                    this.updateConnectionStatus(false, 'Conectando...');
                    
                    // Obtener configuración WebSocket con timeout
                    console.log(`[${this.connectionId}] Obteniendo configuración WebSocket...`);
                    const controller = new AbortController();
                    const timeoutId = setTimeout(() => controller.abort(), 10000); // 10s timeout
                    
                    const response = await fetch('/api/websocket/info', {
                        signal: controller.signal
                    });
                    clearTimeout(timeoutId);
                    
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    
                    const config = await response.json();
                    console.log(`[${this.connectionId}] Configuración WebSocket:`, config);

                    // Almacenar configuración para reconexiones
                    this.wsConfig = config;
                    this.maxReconnectAttempts = config.max_reconnect_attempts || 5;
                    this.exponentialBackoff = config.exponential_backoff !== false;
                    this.maxBackoffDelay = config.max_backoff_delay || 60;

                    await this.connectWebSocket(config);

                } catch (error) {
                    console.error(`[${this.connectionId}] Error inicializando WebSocket:`, error);
                    this.connectionState = 'failed';
                    this.updateConnectionStatus(false, 'Error de conexión');
                    this.log('Error conectando WebSocket: ' + error.message);
                    this.scheduleReconnect();
                }
            }

            async connectWebSocket(config) {
                return new Promise((resolve, reject) => {
                    try {
                        console.log(`[${this.connectionId}] Intentando conectar WebSocket...`);
                        console.log(`[${this.connectionId}] Session ID: ${this.sessionId}, Tab ID: ${this.tabId}`);
                        console.log(`[${this.connectionId}] 🚀 Configuración Pusher WebSocket:`);
                        console.log(`[${this.connectionId}] - App Key: ${config.app_key}`);
                        console.log(`[${this.connectionId}] - Host: ${config.reverb_host}`);
                        console.log(`[${this.connectionId}] - Puerto: ${config.reverb_port}`);
                        console.log(`[${this.connectionId}] - Canal objetivo: ${config.channels.main}`);
                        
                        this.pusher = new Pusher(config.app_key, {
                            wsHost: config.reverb_host,
                            wsPort: config.reverb_port,
                            wssPort: config.reverb_port,
                            forceTLS: false,
                            enabledTransports: ['ws'],
                            disableStats: true,
                            cluster: 'mt1',
                            encrypted: false,
                            debug: false, // Debug desactivado para producción
                            connectionTimeout: 10000,
                            activityTimeout: 120000,
                            // Metadatos de la conexión
                            auth: {
                                headers: {
                                    'X-Connection-ID': this.connectionId,
                                    'X-Session-ID': this.sessionId,
                                    'X-Tab-ID': this.tabId,
                                    'X-User-Agent': navigator.userAgent.substring(0, 100)
                                }
                            }
                        });

                        // Bind eventos de conexión con mejor manejo
                        this.pusher.connection.bind('connected', () => {
                            console.log(`[${this.connectionId}] ✅ WebSocket conectado exitosamente`);
                            console.log(`[${this.connectionId}] Socket ID:`, this.pusher.connection.socket_id);
                            console.log(`[${this.connectionId}] Estado de conexión:`, this.pusher.connection.state);
                            this.connectionState = 'connected';
                            this.reconnectAttempts = 0;
                            this.currentReconnectDelay = this.baseReconnectDelay;
                            this.updateConnectionStatus(true, 'Conectado');
                            this.log(`✅ WebSocket conectado (Socket: ${this.pusher.connection.socket_id.substring(0, 8)}...)`);
                            this.startHeartbeat();
                            resolve();
                        });

                        this.pusher.connection.bind('disconnected', () => {
                            console.log(`[${this.connectionId}] ❌ WebSocket desconectado`);
                            console.log(`[${this.connectionId}] Estado de conexión:`, this.pusher.connection.state);
                            this.connectionState = 'disconnected';
                            this.updateConnectionStatus(false, 'Desconectado');
                            this.log('❌ Desconectado de WebSocket');
                            this.stopHeartbeat();
                            
                            // Solo reconectar si la pestaña está visible
                            if (this.isVisible) {
                                this.scheduleReconnect();
                            } else {
                                console.log(`[${this.connectionId}] Pestaña no visible, posponiendo reconexión`);
                            }
                        });

                        this.pusher.connection.bind('error', (error) => {
                            console.error(`[${this.connectionId}] ⚠️ Error de WebSocket:`, error);
                            this.connectionState = 'error';
                            this.updateConnectionStatus(false, 'Error');
                            this.log('⚠️ Error WebSocket: ' + (error.message || 'Error desconocido'));
                            reject(error);
                        });

                        this.pusher.connection.bind('failed', () => {
                            console.error(`[${this.connectionId}] ❌ Conexión WebSocket falló`);
                            this.connectionState = 'failed';
                            this.updateConnectionStatus(false, 'Conexión falló');
                            this.log('❌ Conexión WebSocket falló');
                            
                            if (this.isVisible) {
                                this.scheduleReconnect();
                            }
                            reject(new Error('WebSocket connection failed'));
                        });

                        // Suscribirse a canales después de la conexión
                        this.pusher.connection.bind('connected', () => {
                            this.subscribeToChannels(config);
                        });

                        // Timeout para detectar conexión fallida
                        setTimeout(() => {
                            if (this.connectionState === 'connecting') {
                                console.log(`[${this.connectionId}] Timeout de conexión, activando solo polling...`);
                                this.connectionState = 'timeout';
                                this.updateConnectionStatus(false, 'Timeout');
                                this.log('WebSocket timeout, usando polling');
                                reject(new Error('Connection timeout'));
                            }
                        }, 15000);

                    } catch (error) {
                        console.error(`[${this.connectionId}] Error creando conexión WebSocket:`, error);
                        reject(error);
                    }
                });
            }

            subscribeToChannels(config) {
                try {
                    this.channel = this.pusher.subscribe(config.channels.main);
                    console.log(`[${this.connectionId}] 📡 Suscrito al canal:`, config.channels.main);
                    console.log(`[${this.connectionId}] Socket ID en suscripción:`, this.pusher.connection.socket_id);
                    
                    this.channel.bind('weather.updated', (data) => {
                        // Solo procesar si la pestaña está visible o han pasado pocos segundos desde la última actividad
                        const timeSinceActivity = Date.now() - this.lastActivity;
                        if (!this.isVisible && timeSinceActivity > 30000) { // 30 segundos
                            console.log(`[${this.connectionId}] 🔕 Evento weather.updated ignorado (pestaña inactiva)`);
                            return;
                        }
                        
                        console.log(`[${this.connectionId}] 🌡️ Evento weather.updated recibido vía WebSocket:`, data);
                        this.log(`🔴 WebSocket: ${data.location.name} actualizado a ${data.weather.temperature}°C`);
                        this.handleWeatherUpdate(data);
                    });

                    this.channel.bind('weather.summary', (data) => {
                        // Solo procesar si la pestaña está visible o han pasado pocos segundos desde la última actividad
                        const timeSinceActivity = Date.now() - this.lastActivity;
                        if (!this.isVisible && timeSinceActivity > 30000) { // 30 segundos
                            console.log(`[${this.connectionId}] 🔕 Evento weather.summary ignorado (pestaña inactiva)`);
                            return;
                        }
                        
                        console.log(`[${this.connectionId}] 📊 Evento weather.summary recibido vía WebSocket:`, data);
                        console.log(`[${this.connectionId}] 📊 Datos del evento:`, JSON.stringify(data, null, 2));
                        console.log(`[${this.connectionId}] 📊 Tipo de data:`, typeof data, Array.isArray(data));
                        console.log(`[${this.connectionId}] 📊 data.summary:`, data.summary, Array.isArray(data.summary));
                        this.log(`🔴 WebSocket: Resumen completo actualizado (${data.total_locations || data.length || 'desconocido'} ubicaciones)`);
                        
                        // Los datos del WebSocket vienen con estructura {summary: [...], total_locations: X, timestamp: Y}
                        const summaryData = data.summary || data;
                        this.handleSummaryUpdate(summaryData, 'websocket');
                    });

                    // Bind eventos específicos del canal
                    this.channel.bind('pusher:subscription_succeeded', (data) => {
                        console.log(`[${this.connectionId}] ✅ Suscripción exitosa al canal`);
                        this.log(`✅ Suscrito exitosamente al canal de tiempo real`);
                    });

                    this.channel.bind('pusher:subscription_error', (error) => {
                        console.error(`[${this.connectionId}] ❌ Error de suscripción:`, error);
                        this.log(`❌ Error suscribiendo al canal: ${error.message || 'Error desconocido'}`);
                    });

                    // Bind eventos de heartbeat
                    this.channel.bind('pusher:pong', (data) => {
                        this.lastPongReceived = Date.now();
                        console.log(`[${this.connectionId}] 💓 Pong recibido`);
                    });

                } catch (error) {
                    console.error(`[${this.connectionId}] Error suscribiendo a canales:`, error);
                    this.log(`Error suscribiendo a canales: ${error.message}`);
                }
            }

            scheduleReconnect() {
                if (this.reconnectAttempts >= this.maxReconnectAttempts) {
                    console.log(`[${this.connectionId}] Max intentos de reconexión WebSocket alcanzados (${this.maxReconnectAttempts})`);
                    console.log(`[${this.connectionId}] Activando polling como respaldo permanente...`);
                    this.connectionState = 'max_attempts_reached';
                    this.updateConnectionStatus(false, 'Sin WebSocket - Usando polling');
                    this.log(`WebSocket falló permanentemente, usando polling como respaldo`);
                    
                    // Solo ahora activar polling como último recurso
                    this.startPolling();
                    return;
                }

                this.reconnectAttempts++;
                
                // Calcular delay con backoff exponencial
                if (this.exponentialBackoff) {
                    this.currentReconnectDelay = Math.min(
                        this.baseReconnectDelay * Math.pow(2, this.reconnectAttempts - 1),
                        this.maxBackoffDelay
                    );
                } else {
                    this.currentReconnectDelay = this.baseReconnectDelay;
                }

                console.log(`[${this.connectionId}] Programando reconexión WebSocket ${this.reconnectAttempts}/${this.maxReconnectAttempts} en ${this.currentReconnectDelay}s`);
                this.updateConnectionStatus(false, `Reconectando WebSocket en ${this.currentReconnectDelay}s...`);
                this.log(`Reconexión WebSocket ${this.reconnectAttempts}/${this.maxReconnectAttempts} en ${this.currentReconnectDelay}s`);

                if (this.reconnectTimer) {
                    clearTimeout(this.reconnectTimer);
                }

                this.reconnectTimer = setTimeout(() => {
                    this.reconnect();
                }, this.currentReconnectDelay * 1000);
            }

            async reconnect() {
                if (this.connectionState === 'connected') {
                    console.log(`[${this.connectionId}] Ya conectado, cancelando reconexión`);
                    return;
                }

                console.log(`[${this.connectionId}] Intentando reconexión...`);
                this.connectionState = 'reconnecting';
                this.updateConnectionStatus(false, 'Reconectando...');

                try {
                    // Limpiar conexión anterior
                    if (this.pusher) {
                        this.pusher.disconnect();
                        this.pusher = null;
                        this.channel = null;
                    }

                    await this.connectWebSocket(this.wsConfig);
                    
                } catch (error) {
                    console.error(`[${this.connectionId}] Error en reconexión:`, error);
                    this.scheduleReconnect();
                }
            }

            startHeartbeat() {
                this.stopHeartbeat();
                this.lastPongReceived = Date.now();
                
                this.heartbeatTimer = setInterval(() => {
                    if (this.pusher && this.pusher.connection.state === 'connected') {
                        console.log(`[${this.connectionId}] Enviando ping...`);
                        this.pusher.connection.send_event('pusher:ping', {});
                        
                        // Verificar si se recibió pong
                        setTimeout(() => {
                            const timeSinceLastPong = Date.now() - this.lastPongReceived;
                            if (timeSinceLastPong > 35000) { // 35 segundos sin pong
                                console.log(`[${this.connectionId}] Heartbeat timeout, reconectando...`);
                                this.log('Heartbeat timeout, reconectando...');
                                this.pusher.disconnect();
                            }
                        }, 10000); // Esperar 10s por pong
                    }
                }, 30000); // Ping cada 30 segundos
            }

            stopHeartbeat() {
                if (this.heartbeatTimer) {
                    clearInterval(this.heartbeatTimer);
                    this.heartbeatTimer = null;
                }
            }

            // Función para reconectar manualmente
            manualReconnect() {
                console.log(`[${this.connectionId}] Reconexión manual iniciada`);
                this.reconnectAttempts = 0;
                this.currentReconnectDelay = this.baseReconnectDelay;
                
                if (this.reconnectTimer) {
                    clearTimeout(this.reconnectTimer);
                    this.reconnectTimer = null;
                }
                
                this.reconnect();
            }

            // Función para forzar actualización manual
            forceRefresh() {
                console.log(`[${this.connectionId}] Actualización manual forzada`);
                this.log('Actualización manual forzada');
                this.updatesCount++;
                document.getElementById('updates-count').textContent = this.updatesCount;
                this.loadLatestData();
            }

            startPolling() {
                console.log(`[${this.connectionId}] Iniciando polling como respaldo (cada 10 segundos)`);
                this.log('Polling activado como respaldo - WebSocket no disponible');
                
                // Limpiar polling anterior si existe
                if (this.pollingInterval) {
                    clearInterval(this.pollingInterval);
                }
                
                // Configurar polling cada 10 segundos (menos frecuente, solo respaldo)
                this.pollingInterval = setInterval(() => {
                    console.log(`[${this.connectionId}] Ejecutando polling de respaldo...`);
                    this.loadLatestData();
                }, 10000);
                
                console.log(`[${this.connectionId}] Polling de respaldo configurado correctamente`);
            }

            async loadLatestData() {
                try {
                    console.log(`[${this.connectionId}] Iniciando polling de datos...`);
                    const response = await fetch('/api/geospatial/summary');
                    if (response.ok) {
                        const result = await response.json();
                        console.log(`[${this.connectionId}] Datos recibidos del polling:`, result);
                        
                        if (result.status === 'success' && result.data) {
                            console.log(`[${this.connectionId}] Procesando`, result.data.length, 'ubicaciones vía polling');
                            this.handleSummaryUpdate(result.data, 'polling');
                            this.updateLastUpdate();
                            
                            // Incrementar contador de actualizaciones
                            this.updatesCount++;
                            document.getElementById('updates-count').textContent = this.updatesCount;
                            
                            console.log(`[${this.connectionId}] Actualizaciones incrementadas a:`, this.updatesCount);
                            this.log(`Datos actualizados vía polling - ${result.data.length} ubicaciones`);
                        } else {
                            console.log(`[${this.connectionId}] Formato de respuesta inesperado:`, result);
                        }
                    } else {
                        console.log(`[${this.connectionId}] Error en respuesta HTTP:`, response.status);
                    }
                } catch (error) {
                    console.error(`[${this.connectionId}] Error cargando datos:`, error);
                    this.log('Error cargando datos: ' + error.message);
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
                
                // Mostrar estado de carga
                const submitButton = form.querySelector('button[type="submit"]');
                const originalText = submitButton.textContent;
                submitButton.textContent = 'Agregando...';
                submitButton.disabled = true;
                
                try {
                    this.log(`🔄 Agregando ubicación: ${data.name}...`);
                    
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
                        this.log(`✅ Ubicación agregada: ${data.name}`);
                        form.reset();
                        
                        // Cargar datos inmediatamente para mostrar la nueva ubicación
                        this.log(`🔄 Actualizando vista...`);
                        await this.loadLatestData();
                        
                        // Opcionalmente, solicitar actualización inmediata de esa ubicación específica
                        try {
                            this.log(`🌡️ Obteniendo datos meteorológicos para ${data.name}...`);
                            const updateResponse = await fetch(`/api/geospatial/locations/${result.data.location.id}/update`, {
                                method: 'PUT',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                                }
                            });
                            
                            if (updateResponse.ok) {
                                this.log(`✅ Datos meteorológicos actualizados para ${data.name}`);
                                // Cargar datos actualizados después de un momento
                                setTimeout(() => {
                                    this.loadLatestData();
                                    this.log(`🔔 ${data.name} lista y visible para todos los usuarios`);
                                }, 2000);
                            }
                        } catch (updateError) {
                            console.warn('No se pudo solicitar actualización inmediata:', updateError);
                            this.log(`⚠️ Datos meteorológicos se actualizarán en el próximo ciclo`);
                        }
                        
                    } else {
                        this.log('❌ Error agregando ubicación: ' + result.message);
                    }
                } catch (error) {
                    console.error('Error agregando ubicación:', error);
                    this.log('❌ Error agregando ubicación: ' + error.message);
                } finally {
                    // Restaurar botón
                    submitButton.textContent = originalText;
                    submitButton.disabled = false;
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

            handleSummaryUpdate(data, source = 'unknown') {
                if (this.isUpdating) return; // Prevenir actualizaciones concurrentes
                this.isUpdating = true;
                
                const sourceLabel = source === 'websocket' ? '🔴 WebSocket' : source === 'polling' ? '🔵 Polling' : '⚪ Desconocido';
                
                // Validar y normalizar datos
                let summaryArray;
                if (Array.isArray(data)) {
                    summaryArray = data;
                } else if (data && Array.isArray(data.summary)) {
                    summaryArray = data.summary;
                } else if (data && typeof data === 'object') {
                    console.error(`[${this.connectionId}] ${sourceLabel} - Formato de datos inesperado:`, data);
                    this.isUpdating = false;
                    return;
                } else {
                    console.error(`[${this.connectionId}] ${sourceLabel} - Datos inválidos:`, data);
                    this.isUpdating = false;
                    return;
                }
                
                console.log(`[${this.connectionId}] ${sourceLabel} - handleSummaryUpdate recibido con ${summaryArray.length} ubicaciones`);
                
                summaryArray.forEach(locationData => {
                    console.log(`[${this.connectionId}] ${sourceLabel} - ${locationData.location.name}: ${locationData.weather.temperature}°C`);
                });
                
                try {
                    // Actualizar estadísticas generales
                    this.updateStats({
                        total_locations: summaryArray.length,
                        active_locations: summaryArray.length,
                        active_alerts: summaryArray.filter(item => this.isAlert(item.weather)).length
                    });
                    
                    // Verificar si necesitamos renderizar las ubicaciones por primera vez
                    const locationsContainer = document.getElementById('locations-list');
                    console.log(`[${this.connectionId}] ${sourceLabel} - Contenedor de ubicaciones tiene ${locationsContainer.children.length} hijos`);
                    
                    // Limpiar completamente antes de renderizar
                    console.log(`[${this.connectionId}] ${sourceLabel} - Limpiando contenedor y renderizando ${summaryArray.length} ubicaciones`);
                    locationsContainer.innerHTML = '';
                    
                    summaryArray.forEach((locationData, index) => {
                        console.log(`[${this.connectionId}] ${sourceLabel} - Renderizando ubicación ${index + 1}: ${locationData.location.name} - ${locationData.weather.temperature}°C`);
                        this.addLocationToListFromSummary(locationData, locationsContainer);
                        this.addLocationToMapFromSummary(locationData);
                    });
                    
                    console.log(`[${this.connectionId}] ${sourceLabel} - Renderizado completado - ${locationsContainer.children.length} ubicaciones en contenedor`);
                } finally {
                    this.isUpdating = false;
                }
                
                this.log(`${sourceLabel}: ${summaryArray.length} ubicaciones renderizadas completamente`);
                console.log(`[${this.connectionId}] ${sourceLabel} - handleSummaryUpdate finalizado exitosamente`);
            }

            addLocationToListFromSummary(locationData, container) {
                console.log('Añadiendo ubicación a la lista:', locationData.location.name);
                
                const location = locationData.location;
                const weather = locationData.weather;
                
                const item = document.createElement('div');
                item.className = 'border border-gray-200 rounded-lg p-4';
                item.setAttribute('data-location-id', location.id);
                item.innerHTML = `
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="font-semibold text-gray-800">${location.name}</h3>
                            <p class="text-sm text-gray-600">${location.city}, ${location.country}</p>
                            <div class="mt-2 space-y-1">
                                <div class="flex items-center text-sm">
                                    <span class="mr-2">🌡️</span>
                                    <span class="temperature">${weather.temperature}°C</span>
                                    <span class="weather-description ml-1">(${weather.weather_description})</span>
                                </div>
                                <div class="flex items-center text-sm">
                                    <span class="mr-2">💨</span>
                                    <span class="wind-speed">${weather.wind_speed || 0} km/h</span>
                                </div>
                                <div class="flex items-center text-sm">
                                    <span class="mr-2">💧</span>
                                    <span class="humidity">${weather.humidity}%</span>
                                </div>
                                <div class="text-xs text-gray-400 mt-1">
                                    Actualizado: ${new Date(weather.last_update).toLocaleString()}
                                </div>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="inline-block w-3 h-3 bg-green-500 rounded-full"></span>
                            <span class="text-xs text-gray-500 ml-1">Activo</span>
                        </div>
                    </div>
                `;
                
                container.appendChild(item);
                console.log('Ubicación añadida al contenedor. Total ubicaciones:', container.children.length);
            }

            addLocationToMapFromSummary(locationData) {
                const location = locationData.location;
                const weather = locationData.weather;
                const coords = location.coordinates.split(',');
                const latitude = parseFloat(coords[0]);
                const longitude = parseFloat(coords[1]);
                
                // Remover marcador existente si existe
                if (this.markers[location.id]) {
                    this.map.removeLayer(this.markers[location.id]);
                }
                
                const marker = L.marker([latitude, longitude]).addTo(this.map);
                
                const popupContent = `
                    <strong>${location.name}</strong><br>
                    ${location.city}, ${location.country}<br>
                    🌡️ ${weather.temperature}°C (${weather.weather_description})<br>
                    💨 ${weather.wind_speed || 0} km/h<br>
                    💧 ${weather.humidity}%
                `;
                
                marker.bindPopup(popupContent);
                this.markers[location.id] = marker;
                
                console.log(`Marcador añadido para ${location.name} en [${latitude}, ${longitude}]`);
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

            updateConnectionStatus(connected, statusText = null) {
                const statusElement = document.getElementById('connection-status');
                const dot = statusElement.querySelector('div');
                const text = statusElement.querySelector('span');
                const reconnectBtn = document.getElementById('reconnect-btn');
                
                if (connected) {
                    dot.className = 'w-3 h-3 bg-green-500 rounded-full mr-2';
                    const socketId = this.pusher && this.pusher.connection.socket_id ? 
                        this.pusher.connection.socket_id.substring(0, 8) : 'N/A';
                    text.textContent = statusText || `Conectado (${socketId}...)`;
                    reconnectBtn.classList.add('hidden');
                } else {
                    // Diferentes colores según el estado
                    const state = this.connectionState;
                    if (state === 'connecting' || state === 'reconnecting') {
                        dot.className = 'w-3 h-3 bg-yellow-500 rounded-full mr-2 animate-pulse';
                        reconnectBtn.classList.add('hidden');
                    } else if (state === 'max_attempts_reached') {
                        dot.className = 'w-3 h-3 bg-gray-500 rounded-full mr-2';
                        reconnectBtn.classList.remove('hidden');
                    } else {
                        dot.className = 'w-3 h-3 bg-red-500 rounded-full mr-2';
                        if (state === 'failed' || state === 'error' || state === 'timeout') {
                            reconnectBtn.classList.remove('hidden');
                        } else {
                            reconnectBtn.classList.add('hidden');
                        }
                    }
                    
                    text.textContent = statusText || 'Desconectado';
                }
                
                // Mostrar información adicional en el título incluindo identificadores de pestaña
                const connectionInfo = [
                    `Estado: ${this.connectionState}`,
                    `Pestaña: ${this.tabId.substr(-8)}`,
                    `Sesión: ${this.sessionId.substr(-8)}`,
                    `Intentos: ${this.reconnectAttempts}/${this.maxReconnectAttempts}`,
                    `Visible: ${this.isVisible ? 'Sí' : 'No'}`,
                    this.pusher && this.pusher.connection.socket_id ? `Socket: ${this.pusher.connection.socket_id.substring(0, 8)}` : ''
                ].filter(Boolean).join(' | ');
                
                statusElement.title = connectionInfo;
                
                // Log para debugging de múltiples conexiones
                console.log(`[${this.connectionId}] Estado actualizado:`, {
                    connected,
                    state: this.connectionState,
                    tabId: this.tabId,
                    sessionId: this.sessionId,
                    isVisible: this.isVisible,
                    socketId: this.pusher && this.pusher.connection.socket_id ? this.pusher.connection.socket_id : null
                });
            }

            updateLastUpdate() {
                const lastUpdateElement = document.getElementById('last-update');
                if (lastUpdateElement) {
                    const now = new Date();
                    lastUpdateElement.textContent = now.toLocaleTimeString('es-ES', {
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit'
                    });
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
                if (stats.websocket_connections !== undefined) {
                    document.getElementById('websocket-connections').textContent = stats.websocket_connections;
                }
            }

            startConnectionHeartbeat() {
                // Función para enviar heartbeat
                const sendHeartbeat = async () => {
                    try {
                        const response = await fetch('/api/websocket/heartbeat', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                            },
                            body: JSON.stringify({
                                connection_id: this.connectionId,
                                tab_id: this.tabId,
                                session_id: this.sessionId,
                                url: window.location.href,
                                user_agent: navigator.userAgent.substring(0, 100),
                                timestamp: Date.now(),
                                websocket_connected: this.connectionState === 'connected'
                            })
                        });
                        
                        if (response.ok) {
                            console.log(`[${this.connectionId}] 💓 Usuario activo registrado`);
                        }
                    } catch (error) {
                        console.warn(`[${this.connectionId}] Error enviando heartbeat:`, error);
                    }
                };
                
                // Enviar heartbeat cada 15 segundos (más frecuente para mejor precisión)
                this.connectionHeartbeat = setInterval(sendHeartbeat, 15000);
                
                // Enviar heartbeat inicial inmediatamente
                setTimeout(sendHeartbeat, 500);
            }

            async updateWebSocketStats() {
                try {
                    const response = await fetch('/api/websocket/stats');
                    const stats = await response.json();
                    
                    console.log(`[${this.connectionId}] 📊 Estadísticas de usuarios:`, stats);
                    
                    const connectionsElement = document.getElementById('websocket-connections');
                    
                    if (stats.status === 'online') {
                        connectionsElement.innerHTML = `
                            <span class="text-green-600">${stats.active_users}</span>
                            <span class="text-xs text-gray-500 block">
                                WebSocket: ${stats.active_connections} | IPs: ${stats.unique_ips}
                            </span>
                        `;
                        connectionsElement.title = `
                            Usuarios activos: ${stats.active_users}
                            Conexiones WebSocket: ${stats.active_connections}
                            IPs únicas: ${stats.unique_ips}
                            Servidor: ${stats.status}
                            URL monitoreada: ${stats.url_monitored}
                        `.trim();
                    } else {
                        connectionsElement.innerHTML = `
                            <span class="text-red-600">0</span>
                            <span class="text-xs text-red-500 block">Servidor offline</span>
                        `;
                        connectionsElement.title = `Servidor: ${stats.status}`;
                    }
                    
                } catch (error) {
                    console.error(`[${this.connectionId}] ❌ Error obteniendo estadísticas:`, error);
                    const connectionsElement = document.getElementById('websocket-connections');
                    connectionsElement.innerHTML = `
                        <span class="text-red-600">?</span>
                        <span class="text-xs text-red-500 block">Error</span>
                    `;
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

        // Variable global para acceso desde botones
        let dashboard;

        // Inicializar dashboard cuando se carga la página
        document.addEventListener('DOMContentLoaded', () => {
            dashboard = new GeospatialDashboard();
        });
    </script>
</body>
</html>
