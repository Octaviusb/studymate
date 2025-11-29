// Offline Manager - StudyMate SaaS
class OfflineManager {
    constructor() {
        this.isOnline = navigator.onLine;
        this.pendingActions = JSON.parse(localStorage.getItem('studymate_pending_actions') || '[]');
        this.cachedData = JSON.parse(localStorage.getItem('studymate_cached_data') || '{}');
        
        this.init();
    }
    
    init() {
        // Detectar cambios de conectividad
        window.addEventListener('online', () => {
            this.isOnline = true;
            this.showConnectionStatus('Conexión restaurada', 'success');
            this.syncPendingActions();
        });
        
        window.addEventListener('offline', () => {
            this.isOnline = false;
            this.showConnectionStatus('Sin conexión - Modo offline activado', 'warning');
        });
        
        // Mostrar estado inicial
        if (!this.isOnline) {
            this.showConnectionStatus('Sin conexión - Modo offline', 'warning');
        }
    }
    
    showConnectionStatus(message, type) {
        const statusBar = document.getElementById('connection-status') || this.createStatusBar();
        statusBar.className = `connection-status ${type}`;
        statusBar.textContent = message;
        statusBar.style.display = 'block';
        
        if (type === 'success') {
            setTimeout(() => {
                statusBar.style.display = 'none';
            }, 3000);
        }
    }
    
    createStatusBar() {
        const statusBar = document.createElement('div');
        statusBar.id = 'connection-status';
        statusBar.style.cssText = `
            position: fixed; top: 0; left: 0; right: 0; z-index: 9999;
            padding: 10px; text-align: center; font-weight: bold;
            display: none; transition: all 0.3s ease;
        `;
        
        const style = document.createElement('style');
        style.textContent = `
            .connection-status.success { background: #27ae60; color: white; }
            .connection-status.warning { background: #f39c12; color: white; }
            .connection-status.error { background: #e74c3c; color: white; }
        `;
        document.head.appendChild(style);
        document.body.appendChild(statusBar);
        
        return statusBar;
    }
    
    // Cachear datos importantes
    cacheData(key, data) {
        this.cachedData[key] = {
            data: data,
            timestamp: Date.now(),
            expires: Date.now() + (24 * 60 * 60 * 1000) // 24 horas
        };
        localStorage.setItem('studymate_cached_data', JSON.stringify(this.cachedData));
    }
    
    // Obtener datos del cache
    getCachedData(key) {
        const cached = this.cachedData[key];
        if (cached && cached.expires > Date.now()) {
            return cached.data;
        }
        return null;
    }
    
    // Agregar acción pendiente para sincronizar
    addPendingAction(action) {
        this.pendingActions.push({
            ...action,
            timestamp: Date.now(),
            id: Date.now() + Math.random()
        });
        localStorage.setItem('studymate_pending_actions', JSON.stringify(this.pendingActions));
    }
    
    // Sincronizar acciones pendientes cuando vuelva la conexión
    async syncPendingActions() {
        if (this.pendingActions.length === 0) return;
        
        const actionsToSync = [...this.pendingActions];
        this.pendingActions = [];
        localStorage.setItem('studymate_pending_actions', JSON.stringify(this.pendingActions));
        
        let syncedCount = 0;
        
        for (const action of actionsToSync) {
            try {
                await this.executeAction(action);
                syncedCount++;
            } catch (error) {
                console.error('Error sincronizando acción:', error);
                // Volver a agregar a pendientes si falla
                this.addPendingAction(action);
            }
        }
        
        if (syncedCount > 0) {
            UIComponents.showToast(`${syncedCount} acciones sincronizadas`, 'success');
        }
    }
    
    async executeAction(action) {
        const response = await fetch(action.url, {
            method: action.method || 'POST',
            headers: action.headers || { 'Content-Type': 'application/json' },
            body: action.body
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        
        return response.json();
    }
    
    // Wrapper para fetch que maneja offline
    async safeFetch(url, options = {}) {
        if (!this.isOnline) {
            // Si es una operación de escritura, guardar para sincronizar después
            if (options.method && options.method !== 'GET') {
                this.addPendingAction({
                    url: url,
                    method: options.method,
                    headers: options.headers,
                    body: options.body
                });
                
                UIComponents.showToast('Acción guardada para sincronizar cuando vuelva la conexión', 'info');
                return { success: true, offline: true };
            }
            
            // Para operaciones de lectura, intentar usar cache
            const cacheKey = url + JSON.stringify(options);
            const cachedData = this.getCachedData(cacheKey);
            
            if (cachedData) {
                UIComponents.showToast('Datos cargados desde cache (offline)', 'info');
                return cachedData;
            }
            
            throw new Error('Sin conexión y no hay datos en cache');
        }
        
        try {
            const response = await fetch(url, options);
            const data = await response.json();
            
            // Cachear respuestas exitosas de lectura
            if (response.ok && (!options.method || options.method === 'GET')) {
                const cacheKey = url + JSON.stringify(options);
                this.cacheData(cacheKey, data);
            }
            
            return data;
        } catch (error) {
            // Si falla la conexión, intentar cache como fallback
            if (!this.isOnline) {
                const cacheKey = url + JSON.stringify(options);
                const cachedData = this.getCachedData(cacheKey);
                
                if (cachedData) {
                    UIComponents.showToast('Datos cargados desde cache (conexión falló)', 'warning');
                    return cachedData;
                }
            }
            
            throw error;
        }
    }
    
    // Limpiar cache expirado
    cleanExpiredCache() {
        const now = Date.now();
        Object.keys(this.cachedData).forEach(key => {
            if (this.cachedData[key].expires < now) {
                delete this.cachedData[key];
            }
        });
        localStorage.setItem('studymate_cached_data', JSON.stringify(this.cachedData));
    }
    
    // Obtener estadísticas offline
    getOfflineStats() {
        return {
            isOnline: this.isOnline,
            pendingActions: this.pendingActions.length,
            cachedItems: Object.keys(this.cachedData).length,
            cacheSize: JSON.stringify(this.cachedData).length
        };
    }
}

// Inicializar manager offline global
window.offlineManager = new OfflineManager();

// Limpiar cache cada hora
setInterval(() => {
    window.offlineManager.cleanExpiredCache();
}, 60 * 60 * 1000);