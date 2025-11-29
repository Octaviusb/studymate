// Sistema de Comunicación entre Roles
class CommunicationSystem {
    constructor(userId) {
        this.userId = userId;
        this.currentUser = JSON.parse(localStorage.getItem('studymate_user') || '{}');
        this.contacts = [];
        this.messages = [];
    }

    // Cargar contactos disponibles según el rol
    async loadContacts() {
        try {
            const response = await fetch(`communication_api.php?action=get_contacts&user_id=${this.userId}`);
            const data = await response.json();
            
            if (response.ok) {
                this.contacts = data.contacts;
                return this.contacts;
            }
        } catch (error) {
            console.error('Error loading contacts:', error);
        }
        return [];
    }

    // Cargar mensajes del usuario
    async loadMessages() {
        try {
            const response = await fetch(`communication_api.php?action=get_messages&user_id=${this.userId}`);
            const data = await response.json();
            
            if (response.ok) {
                this.messages = data.messages;
                return this.messages;
            }
        } catch (error) {
            console.error('Error loading messages:', error);
        }
        return [];
    }

    // Enviar mensaje
    async sendMessage(recipientId, subject, message) {
        try {
            const response = await fetch('communication_api.php?action=send_message', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    recipient_id: recipientId,
                    subject: subject,
                    message: message
                })
            });
            
            const data = await response.json();
            
            if (response.ok) {
                showToast(`Mensaje enviado a ${data.recipient}`, 'success');
                return true;
            } else {
                showToast(data.message, 'error');
                return false;
            }
        } catch (error) {
            console.error('Error sending message:', error);
            showToast('Error al enviar mensaje', 'error');
            return false;
        }
    }

    // Programar reunión
    async scheduleMeeting(participantId, date, time, subject) {
        try {
            const response = await fetch('communication_api.php?action=schedule_meeting', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    participant_id: participantId,
                    date: date,
                    time: time,
                    subject: subject
                })
            });
            
            const data = await response.json();
            
            if (response.ok) {
                showToast('Reunión programada exitosamente', 'success');
                return true;
            } else {
                showToast(data.message, 'error');
                return false;
            }
        } catch (error) {
            console.error('Error scheduling meeting:', error);
            showToast('Error al programar reunión', 'error');
            return false;
        }
    }

    // Crear anuncio (solo admin/coordinadores)
    async createAnnouncement(title, content, priority = 'medium') {
        try {
            const response = await fetch('communication_api.php?action=create_announcement', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    title: title,
                    content: content,
                    priority: priority
                })
            });
            
            const data = await response.json();
            
            if (response.ok) {
                showToast('Anuncio creado exitosamente', 'success');
                return true;
            } else {
                showToast(data.message, 'error');
                return false;
            }
        } catch (error) {
            console.error('Error creating announcement:', error);
            showToast('Error al crear anuncio', 'error');
            return false;
        }
    }

    // Obtener anuncios
    async loadAnnouncements() {
        try {
            const response = await fetch(`communication_api.php?action=get_announcements&user_id=${this.userId}`);
            const data = await response.json();
            
            if (response.ok) {
                return data.announcements;
            }
        } catch (error) {
            console.error('Error loading announcements:', error);
        }
        return [];
    }

    // Crear modal de comunicación
    createCommunicationModal() {
        const modal = document.createElement('div');
        modal.id = 'communication-modal';
        modal.className = 'communication-modal';
        modal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h3>💬 Comunicación</h3>
                    <button class="close-btn" onclick="closeCommunicationModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="comm-tabs">
                        <button class="comm-tab active" onclick="showCommTab('messages')">📧 Mensajes</button>
                        <button class="comm-tab" onclick="showCommTab('send')">✍️ Enviar</button>
                        <button class="comm-tab" onclick="showCommTab('meetings')">📅 Reuniones</button>
                        ${this.canCreateAnnouncements() ? '<button class="comm-tab" onclick="showCommTab(\'announcements\')">📢 Anuncios</button>' : ''}
                    </div>
                    
                    <!-- Messages Tab -->
                    <div id="messages-tab" class="comm-tab-content active">
                        <div id="messages-list">Cargando mensajes...</div>
                    </div>
                    
                    <!-- Send Message Tab -->
                    <div id="send-tab" class="comm-tab-content">
                        <div class="form-group">
                            <label>Destinatario</label>
                            <select id="message-recipient">
                                <option value="">Seleccionar destinatario</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Asunto</label>
                            <input type="text" id="message-subject" placeholder="Asunto del mensaje">
                        </div>
                        <div class="form-group">
                            <label>Mensaje</label>
                            <textarea id="message-content" rows="4" placeholder="Escriba su mensaje aquí..."></textarea>
                        </div>
                        <button class="btn btn-primary" onclick="sendMessageFromModal()">📧 Enviar Mensaje</button>
                    </div>
                    
                    <!-- Meetings Tab -->
                    <div id="meetings-tab" class="comm-tab-content">
                        <div class="form-group">
                            <label>Participante</label>
                            <select id="meeting-participant">
                                <option value="">Seleccionar participante</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Fecha</label>
                            <input type="date" id="meeting-date">
                        </div>
                        <div class="form-group">
                            <label>Hora</label>
                            <input type="time" id="meeting-time">
                        </div>
                        <div class="form-group">
                            <label>Asunto</label>
                            <input type="text" id="meeting-subject" placeholder="Asunto de la reunión">
                        </div>
                        <button class="btn btn-success" onclick="scheduleMeetingFromModal()">📅 Programar Reunión</button>
                    </div>
                    
                    <!-- Announcements Tab -->
                    ${this.canCreateAnnouncements() ? `
                    <div id="announcements-tab" class="comm-tab-content">
                        <div class="form-group">
                            <label>Título</label>
                            <input type="text" id="announcement-title" placeholder="Título del anuncio">
                        </div>
                        <div class="form-group">
                            <label>Contenido</label>
                            <textarea id="announcement-content" rows="4" placeholder="Contenido del anuncio"></textarea>
                        </div>
                        <div class="form-group">
                            <label>Prioridad</label>
                            <select id="announcement-priority">
                                <option value="low">Baja</option>
                                <option value="medium" selected>Media</option>
                                <option value="high">Alta</option>
                            </select>
                        </div>
                        <button class="btn btn-warning" onclick="createAnnouncementFromModal()">📢 Crear Anuncio</button>
                    </div>
                    ` : ''}
                </div>
            </div>
        `;

        // Agregar estilos
        const style = document.createElement('style');
        style.textContent = `
            .communication-modal {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                z-index: 1000;
                display: none;
                align-items: center;
                justify-content: center;
            }
            .communication-modal .modal-content {
                background: white;
                border-radius: 8px;
                width: 90%;
                max-width: 600px;
                max-height: 80vh;
                overflow: hidden;
            }
            .communication-modal .modal-header {
                background: #3498db;
                color: white;
                padding: 15px 20px;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .communication-modal .close-btn {
                background: none;
                border: none;
                color: white;
                font-size: 24px;
                cursor: pointer;
            }
            .communication-modal .modal-body {
                padding: 20px;
                max-height: 60vh;
                overflow-y: auto;
            }
            .comm-tabs {
                display: flex;
                border-bottom: 1px solid #ddd;
                margin-bottom: 20px;
            }
            .comm-tab {
                background: none;
                border: none;
                padding: 10px 15px;
                cursor: pointer;
                border-bottom: 2px solid transparent;
            }
            .comm-tab.active {
                border-bottom-color: #3498db;
                color: #3498db;
            }
            .comm-tab-content {
                display: none;
            }
            .comm-tab-content.active {
                display: block;
            }
            .form-group {
                margin-bottom: 15px;
            }
            .form-group label {
                display: block;
                margin-bottom: 5px;
                font-weight: bold;
            }
            .form-group input, .form-group select, .form-group textarea {
                width: 100%;
                padding: 8px;
                border: 1px solid #ddd;
                border-radius: 4px;
            }
            .btn {
                background: #3498db;
                color: white;
                border: none;
                padding: 10px 15px;
                border-radius: 4px;
                cursor: pointer;
            }
            .btn-primary { background: #3498db; }
            .btn-success { background: #27ae60; }
            .btn-warning { background: #f39c12; }
            .message-item {
                border: 1px solid #ddd;
                border-radius: 4px;
                padding: 15px;
                margin-bottom: 10px;
            }
            .message-item.unread {
                background: #e3f2fd;
                border-left: 4px solid #3498db;
            }
            .message-header {
                font-weight: bold;
                margin-bottom: 5px;
            }
            .message-meta {
                font-size: 12px;
                color: #666;
                margin-bottom: 10px;
            }
        `;

        if (!document.querySelector('style[data-communication]')) {
            style.setAttribute('data-communication', 'true');
            document.head.appendChild(style);
        }

        document.body.appendChild(modal);
        return modal;
    }

    canCreateAnnouncements() {
        return ['admin', 'coordinator', 'super_admin'].includes(this.currentUser.role);
    }

    async openCommunicationModal() {
        let modal = document.getElementById('communication-modal');
        if (!modal) {
            modal = this.createCommunicationModal();
        }

        // Cargar datos
        await this.loadContactsInModal();
        await this.loadMessagesInModal();

        modal.style.display = 'flex';
    }

    async loadContactsInModal() {
        const contacts = await this.loadContacts();
        const recipientSelect = document.getElementById('message-recipient');
        const participantSelect = document.getElementById('meeting-participant');

        const options = contacts.map(contact => 
            `<option value="${contact.id}">${contact.full_name} (${contact.role})</option>`
        ).join('');

        if (recipientSelect) recipientSelect.innerHTML = '<option value="">Seleccionar destinatario</option>' + options;
        if (participantSelect) participantSelect.innerHTML = '<option value="">Seleccionar participante</option>' + options;
    }

    async loadMessagesInModal() {
        const messages = await this.loadMessages();
        const messagesList = document.getElementById('messages-list');

        if (messages.length === 0) {
            messagesList.innerHTML = '<p>No hay mensajes</p>';
            return;
        }

        messagesList.innerHTML = messages.map(msg => `
            <div class="message-item ${msg.read ? '' : 'unread'}">
                <div class="message-header">${msg.subject}</div>
                <div class="message-meta">De: ${msg.from_name} (${msg.from_role}) - ${new Date(msg.sent_at).toLocaleString()}</div>
                <div class="message-content">${msg.message}</div>
            </div>
        `).join('');
    }
}

// Funciones globales para el modal
function showCommTab(tabName) {
    document.querySelectorAll('.comm-tab').forEach(tab => tab.classList.remove('active'));
    document.querySelectorAll('.comm-tab-content').forEach(content => content.classList.remove('active'));
    
    event.target.classList.add('active');
    document.getElementById(tabName + '-tab').classList.add('active');
}

function closeCommunicationModal() {
    document.getElementById('communication-modal').style.display = 'none';
}

async function sendMessageFromModal() {
    const recipientId = document.getElementById('message-recipient').value;
    const subject = document.getElementById('message-subject').value;
    const content = document.getElementById('message-content').value;

    if (!recipientId || !subject || !content) {
        showToast('Complete todos los campos', 'warning');
        return;
    }

    const success = await communicationSystem.sendMessage(recipientId, subject, content);
    if (success) {
        document.getElementById('message-subject').value = '';
        document.getElementById('message-content').value = '';
    }
}

async function scheduleMeetingFromModal() {
    const participantId = document.getElementById('meeting-participant').value;
    const date = document.getElementById('meeting-date').value;
    const time = document.getElementById('meeting-time').value;
    const subject = document.getElementById('meeting-subject').value;

    if (!participantId || !date || !time || !subject) {
        showToast('Complete todos los campos', 'warning');
        return;
    }

    const success = await communicationSystem.scheduleMeeting(participantId, date, time, subject);
    if (success) {
        document.getElementById('meeting-date').value = '';
        document.getElementById('meeting-time').value = '';
        document.getElementById('meeting-subject').value = '';
    }
}

async function createAnnouncementFromModal() {
    const title = document.getElementById('announcement-title').value;
    const content = document.getElementById('announcement-content').value;
    const priority = document.getElementById('announcement-priority').value;

    if (!title || !content) {
        showToast('Complete título y contenido', 'warning');
        return;
    }

    const success = await communicationSystem.createAnnouncement(title, content, priority);
    if (success) {
        document.getElementById('announcement-title').value = '';
        document.getElementById('announcement-content').value = '';
    }
}

// Inicializar sistema de comunicación
let communicationSystem = null;

document.addEventListener('DOMContentLoaded', function() {
    const user = JSON.parse(localStorage.getItem('studymate_user') || '{}');
    if (user.id) {
        communicationSystem = new CommunicationSystem(user.id);
    }
});

// Función global para abrir comunicación
function openCommunication() {
    if (communicationSystem) {
        communicationSystem.openCommunicationModal();
    }
}