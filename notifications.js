// Sistema de Notificaciones en Tiempo Real
class NotificationSystem {
    constructor(userId) {
        this.userId = userId;
        this.notifications = [];
        this.isVisible = false;
        this.init();
    }

    init() {
        this.createNotificationUI();
        this.loadNotifications();
        this.startPolling();
    }

    createNotificationUI() {
        // Crear botón de notificaciones
        const notificationBtn = document.createElement('button');
        notificationBtn.id = 'notification-btn';
        notificationBtn.className = 'notification-btn';
        notificationBtn.innerHTML = '🔔 <span id="notification-count">0</span>';
        notificationBtn.onclick = () => this.toggleNotifications();

        // Crear panel de notificaciones
        const notificationPanel = document.createElement('div');
        notificationPanel.id = 'notification-panel';
        notificationPanel.className = 'notification-panel';
        notificationPanel.innerHTML = `
            <div class="notification-header">
                <h3>Notificaciones</h3>
                <button onclick="notificationSystem.markAllRead()">Marcar todas como leídas</button>
            </div>
            <div id="notification-list" class="notification-list">
                <div class="loading">Cargando notificaciones...</div>
            </div>
        `;

        // Agregar estilos
        const style = document.createElement('style');
        style.textContent = `
            .notification-btn {
                position: fixed;
                top: 20px;
                right: 20px;
                background: #3498db;
                color: white;
                border: none;
                border-radius: 50px;
                padding: 10px 15px;
                cursor: pointer;
                font-size: 16px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.2);
                z-index: 1000;
            }
            .notification-btn:hover {
                background: #2980b9;
            }
            #notification-count {
                background: #e74c3c;
                border-radius: 50%;
                padding: 2px 6px;
                font-size: 12px;
                margin-left: 5px;
            }
            .notification-panel {
                position: fixed;
                top: 70px;
                right: 20px;
                width: 350px;
                max-height: 500px;
                background: white;
                border-radius: 8px;
                box-shadow: 0 8px 25px rgba(0,0,0,0.2);
                z-index: 999;
                display: none;
                overflow: hidden;
            }
            .notification-header {
                background: #f8f9fa;
                padding: 15px;
                border-bottom: 1px solid #dee2e6;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .notification-header h3 {
                margin: 0;
                color: #333;
            }
            .notification-header button {
                background: none;
                border: none;
                color: #3498db;
                cursor: pointer;
                font-size: 12px;
            }
            .notification-list {
                max-height: 400px;
                overflow-y: auto;
            }
            .notification-item {
                padding: 15px;
                border-bottom: 1px solid #f1f1f1;
                cursor: pointer;
                transition: background 0.2s;
            }
            .notification-item:hover {
                background: #f8f9fa;
            }
            .notification-item.unread {
                background: #e3f2fd;
                border-left: 4px solid #3498db;
            }
            .notification-item.high {
                border-left: 4px solid #e74c3c;
            }
            .notification-item.medium {
                border-left: 4px solid #f39c12;
            }
            .notification-item.low {
                border-left: 4px solid #27ae60;
            }
            .notification-message {
                font-weight: bold;
                margin-bottom: 5px;
                color: #333;
            }
            .notification-time {
                font-size: 12px;
                color: #666;
            }
            .loading {
                text-align: center;
                padding: 20px;
                color: #666;
            }
        `;

        document.head.appendChild(style);
        document.body.appendChild(notificationBtn);
        document.body.appendChild(notificationPanel);
    }

    async loadNotifications() {
        try {
            const response = await fetch(`notifications_api.php?action=get_notifications&user_id=${this.userId}`);
            const data = await response.json();
            
            if (response.ok) {
                this.notifications = data.notifications;
                this.updateUI();
            }
        } catch (error) {
            console.error('Error loading notifications:', error);
        }
    }

    updateUI() {
        const count = this.notifications.filter(n => !n.read).length;
        document.getElementById('notification-count').textContent = count;
        
        const list = document.getElementById('notification-list');
        if (this.notifications.length === 0) {
            list.innerHTML = '<div class="loading">No hay notificaciones</div>';
            return;
        }

        list.innerHTML = this.notifications.map(notification => `
            <div class="notification-item ${notification.read ? '' : 'unread'} ${notification.priority}" 
                 onclick="notificationSystem.markAsRead(${notification.id})">
                <div class="notification-message">${notification.message}</div>
                <div class="notification-time">Hace ${notification.time}</div>
            </div>
        `).join('');
    }

    toggleNotifications() {
        const panel = document.getElementById('notification-panel');
        this.isVisible = !this.isVisible;
        panel.style.display = this.isVisible ? 'block' : 'none';
    }

    async markAsRead(notificationId) {
        try {
            const response = await fetch('notifications_api.php?action=mark_read', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `notification_id=${notificationId}`
            });
            
            if (response.ok) {
                const notification = this.notifications.find(n => n.id === notificationId);
                if (notification) {
                    notification.read = true;
                    this.updateUI();
                }
            }
        } catch (error) {
            console.error('Error marking notification as read:', error);
        }
    }

    markAllRead() {
        this.notifications.forEach(n => n.read = true);
        this.updateUI();
    }

    startPolling() {
        // Actualizar notificaciones cada 30 segundos
        setInterval(() => {
            this.loadNotifications();
        }, 30000);
    }

    // Método para enviar notificaciones
    async sendNotification(recipientId, message, type = 'info', priority = 'medium') {
        try {
            const response = await fetch('notifications_api.php?action=send_notification', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    recipient_id: recipientId,
                    message: message,
                    type: type,
                    priority: priority,
                    sender_id: this.userId
                })
            });
            
            return response.ok;
        } catch (error) {
            console.error('Error sending notification:', error);
            return false;
        }
    }
}

// Inicializar sistema de notificaciones cuando se carga la página
let notificationSystem = null;

document.addEventListener('DOMContentLoaded', function() {
    const user = JSON.parse(localStorage.getItem('studymate_user') || '{}');
    if (user.id) {
        notificationSystem = new NotificationSystem(user.id);
    }
});

// Función global para mostrar notificaciones toast
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    
    const style = document.createElement('style');
    style.textContent = `
        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 6px;
            color: white;
            font-weight: bold;
            z-index: 1001;
            animation: slideIn 0.3s ease;
        }
        .toast-info { background: #3498db; }
        .toast-success { background: #27ae60; }
        .toast-warning { background: #f39c12; }
        .toast-error { background: #e74c3c; }
        @keyframes slideIn {
            from { transform: translateX(100%); }
            to { transform: translateX(0); }
        }
    `;
    
    if (!document.querySelector('style[data-toast]')) {
        style.setAttribute('data-toast', 'true');
        document.head.appendChild(style);
    }
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.remove();
    }, 4000);
}