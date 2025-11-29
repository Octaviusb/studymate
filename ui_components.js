// UI Components Library - StudyMate SaaS
class UIComponents {
    
    // Modal genérico reutilizable
    static createModal(title, content, actions = []) {
        const modal = document.createElement('div');
        modal.className = 'modal-overlay';
        modal.style.cssText = `
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
            background: rgba(0,0,0,0.5); z-index: 1000; 
            display: flex; align-items: center; justify-content: center;
            animation: fadeIn 0.3s ease;
        `;
        
        const actionsHtml = actions.map(action => 
            `<button class="btn ${action.class || ''}" onclick="${action.onclick}">${action.text}</button>`
        ).join('');
        
        modal.innerHTML = `
            <div class="modal-content" style="
                background: white; padding: 25px; border-radius: 12px; 
                max-width: 600px; width: 90%; max-height: 80vh; overflow-y: auto;
                box-shadow: 0 10px 30px rgba(0,0,0,0.3); animation: slideIn 0.3s ease;
            ">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 style="margin: 0; color: #2c3e50;">${title}</h3>
                    <button onclick="this.closest('.modal-overlay').remove()" style="
                        background: none; border: none; font-size: 24px; cursor: pointer; color: #7f8c8d;
                    ">&times;</button>
                </div>
                <div class="modal-body">${content}</div>
                <div style="text-align: center; margin-top: 20px; gap: 10px; display: flex; justify-content: center;">
                    ${actionsHtml}
                    <button onclick="this.closest('.modal-overlay').remove()" class="btn" style="background: #95a5a6;">Cancelar</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        return modal;
    }
    
    // Loading spinner
    static showLoading(message = 'Cargando...') {
        const loading = document.createElement('div');
        loading.id = 'loading-spinner';
        loading.style.cssText = `
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(255,255,255,0.9); z-index: 2000;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
        `;
        
        loading.innerHTML = `
            <div style="
                width: 50px; height: 50px; border: 4px solid #f3f3f3;
                border-top: 4px solid #3498db; border-radius: 50%;
                animation: spin 1s linear infinite; margin-bottom: 15px;
            "></div>
            <p style="color: #2c3e50; font-weight: bold;">${message}</p>
        `;
        
        document.body.appendChild(loading);
        return loading;
    }
    
    static hideLoading() {
        const loading = document.getElementById('loading-spinner');
        if (loading) loading.remove();
    }
    
    // Toast notifications
    static showToast(message, type = 'info', duration = 3000) {
        const toast = document.createElement('div');
        const colors = {
            success: '#27ae60',
            error: '#e74c3c',
            warning: '#f39c12',
            info: '#3498db'
        };
        
        toast.style.cssText = `
            position: fixed; top: 20px; right: 20px; z-index: 3000;
            background: ${colors[type]}; color: white; padding: 15px 20px;
            border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            animation: slideInRight 0.3s ease; max-width: 400px;
        `;
        
        toast.innerHTML = `
            <div style="display: flex; align-items: center; gap: 10px;">
                <span>${message}</span>
                <button onclick="this.parentElement.parentElement.remove()" style="
                    background: none; border: none; color: white; font-size: 18px; cursor: pointer;
                ">&times;</button>
            </div>
        `;
        
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), duration);
    }
    
    // Validación de formularios
    static validateForm(formId) {
        const form = document.getElementById(formId);
        const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
        let isValid = true;
        
        inputs.forEach(input => {
            if (!input.value.trim()) {
                input.style.borderColor = '#e74c3c';
                input.style.boxShadow = '0 0 5px rgba(231, 76, 60, 0.3)';
                isValid = false;
            } else {
                input.style.borderColor = '#27ae60';
                input.style.boxShadow = '0 0 5px rgba(39, 174, 96, 0.3)';
            }
        });
        
        return isValid;
    }
    
    // Paginación
    static createPagination(totalItems, itemsPerPage, currentPage, onPageChange) {
        const totalPages = Math.ceil(totalItems / itemsPerPage);
        const pagination = document.createElement('div');
        pagination.className = 'pagination';
        pagination.style.cssText = `
            display: flex; justify-content: center; align-items: center; gap: 5px; margin: 20px 0;
        `;
        
        // Botón anterior
        if (currentPage > 1) {
            pagination.innerHTML += `
                <button onclick="${onPageChange}(${currentPage - 1})" class="btn" style="padding: 8px 12px;">‹ Anterior</button>
            `;
        }
        
        // Números de página
        for (let i = Math.max(1, currentPage - 2); i <= Math.min(totalPages, currentPage + 2); i++) {
            const isActive = i === currentPage;
            pagination.innerHTML += `
                <button onclick="${onPageChange}(${i})" class="btn" style="
                    padding: 8px 12px; 
                    background: ${isActive ? '#3498db' : '#ecf0f1'};
                    color: ${isActive ? 'white' : '#2c3e50'};
                ">${i}</button>
            `;
        }
        
        // Botón siguiente
        if (currentPage < totalPages) {
            pagination.innerHTML += `
                <button onclick="${onPageChange}(${currentPage + 1})" class="btn" style="padding: 8px 12px;">Siguiente ›</button>
            `;
        }
        
        return pagination;
    }
}

// CSS Animations
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    @keyframes slideIn {
        from { transform: translateY(-50px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    
    @keyframes slideInRight {
        from { transform: translateX(100%); }
        to { transform: translateX(0); }
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
        outline: none;
        border-color: #3498db;
        box-shadow: 0 0 8px rgba(52, 152, 219, 0.3);
        transition: all 0.3s ease;
    }
    
    .btn {
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }
    
    .btn:active {
        transform: translateY(0);
    }
    
    .table tr:hover {
        background-color: #f8f9fa;
        transition: background-color 0.2s ease;
    }
`;
document.head.appendChild(style);