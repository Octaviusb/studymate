// Lazy Loading System - StudyMate SaaS
class LazyLoader {
    constructor() {
        this.imageObserver = null;
        this.contentObserver = null;
        this.init();
    }
    
    init() {
        // Lazy loading para imágenes
        if ('IntersectionObserver' in window) {
            this.imageObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        this.loadImage(entry.target);
                        this.imageObserver.unobserve(entry.target);
                    }
                });
            }, {
                rootMargin: '50px 0px',
                threshold: 0.01
            });
            
            // Lazy loading para contenido dinámico
            this.contentObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        this.loadContent(entry.target);
                        this.contentObserver.unobserve(entry.target);
                    }
                });
            }, {
                rootMargin: '100px 0px',
                threshold: 0.01
            });
            
            this.observeElements();
        } else {
            // Fallback para navegadores sin soporte
            this.loadAllImages();
        }
    }
    
    observeElements() {
        // Observar imágenes lazy
        document.querySelectorAll('img[data-src]').forEach(img => {
            this.imageObserver.observe(img);
        });
        
        // Observar contenido lazy
        document.querySelectorAll('[data-lazy-content]').forEach(element => {
            this.contentObserver.observe(element);
        });
    }
    
    loadImage(img) {
        // Crear placeholder mientras carga
        const placeholder = this.createImagePlaceholder(img);
        
        const imageLoader = new Image();
        imageLoader.onload = () => {
            img.src = img.dataset.src;
            img.classList.add('loaded');
            img.removeAttribute('data-src');
            
            // Remover placeholder
            if (placeholder && placeholder.parentNode) {
                placeholder.parentNode.removeChild(placeholder);
            }
        };
        
        imageLoader.onerror = () => {
            img.src = this.getErrorImage();
            img.classList.add('error');
        };
        
        imageLoader.src = img.dataset.src;
    }
    
    createImagePlaceholder(img) {
        const placeholder = document.createElement('div');
        placeholder.className = 'image-placeholder';
        placeholder.style.cssText = `
            width: ${img.offsetWidth || 200}px;
            height: ${img.offsetHeight || 150}px;
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
            font-size: 14px;
        `;
        placeholder.textContent = 'Cargando...';
        
        // Insertar placeholder
        if (img.parentNode) {
            img.parentNode.insertBefore(placeholder, img);
            img.style.display = 'none';
        }
        
        return placeholder;
    }
    
    loadContent(element) {
        const contentType = element.dataset.lazyContent;
        
        switch (contentType) {
            case 'students-list':
                this.loadStudentsList(element);
                break;
            case 'grades-table':
                this.loadGradesTable(element);
                break;
            case 'tasks-list':
                this.loadTasksList(element);
                break;
            default:
                this.loadGenericContent(element);
        }
    }
    
    async loadStudentsList(element) {
        element.innerHTML = '<div class="loading-content">Cargando estudiantes...</div>';
        
        try {
            // Simular carga de datos
            await new Promise(resolve => setTimeout(resolve, 500));
            
            const students = [
                { name: 'Ana García', grade: '10A', status: 'Activo' },
                { name: 'Carlos López', grade: '10B', status: 'Activo' },
                { name: 'María Rodríguez', grade: '11A', status: 'Activo' }
            ];
            
            element.innerHTML = students.map(student => `
                <div class="student-item">
                    <strong>${student.name}</strong> - ${student.grade} (${student.status})
                </div>
            `).join('');
            
        } catch (error) {
            element.innerHTML = '<div class="error-content">Error cargando estudiantes</div>';
        }
    }
    
    async loadGradesTable(element) {
        element.innerHTML = '<div class="loading-content">Cargando calificaciones...</div>';
        
        try {
            await new Promise(resolve => setTimeout(resolve, 800));
            
            element.innerHTML = `
                <table class="table">
                    <thead>
                        <tr><th>Materia</th><th>Calificación</th><th>Estado</th></tr>
                    </thead>
                    <tbody>
                        <tr><td>Matemáticas</td><td>8.5</td><td>Aprobado</td></tr>
                        <tr><td>Historia</td><td>7.8</td><td>Aprobado</td></tr>
                        <tr><td>Química</td><td>6.2</td><td>En riesgo</td></tr>
                    </tbody>
                </table>
            `;
            
        } catch (error) {
            element.innerHTML = '<div class="error-content">Error cargando calificaciones</div>';
        }
    }
    
    async loadTasksList(element) {
        element.innerHTML = '<div class="loading-content">Cargando tareas...</div>';
        
        try {
            await new Promise(resolve => setTimeout(resolve, 600));
            
            const tasks = [
                { title: 'Ejercicios Álgebra', subject: 'Matemáticas', due: '15/12/2024' },
                { title: 'Ensayo Historia', subject: 'Historia', due: '18/12/2024' }
            ];
            
            element.innerHTML = tasks.map(task => `
                <div class="task-item">
                    <h4>${task.title}</h4>
                    <p>${task.subject} - Entrega: ${task.due}</p>
                </div>
            `).join('');
            
        } catch (error) {
            element.innerHTML = '<div class="error-content">Error cargando tareas</div>';
        }
    }
    
    loadGenericContent(element) {
        const content = element.dataset.content || 'Contenido cargado';
        element.innerHTML = content;
        element.classList.add('loaded');
    }
    
    getErrorImage() {
        // Imagen de error en base64 (1x1 pixel transparente)
        return 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
    }
    
    loadAllImages() {
        // Fallback: cargar todas las imágenes inmediatamente
        document.querySelectorAll('img[data-src]').forEach(img => {
            img.src = img.dataset.src;
            img.removeAttribute('data-src');
        });
    }
    
    // Método público para observar nuevos elementos
    observe(element) {
        if (element.tagName === 'IMG' && element.dataset.src) {
            this.imageObserver?.observe(element);
        } else if (element.dataset.lazyContent) {
            this.contentObserver?.observe(element);
        }
    }
    
    // Método para desconectar observadores
    disconnect() {
        this.imageObserver?.disconnect();
        this.contentObserver?.disconnect();
    }
}

// CSS para animaciones de carga
const lazyLoadingStyles = `
    @keyframes loading {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }
    
    .image-placeholder {
        transition: opacity 0.3s ease;
    }
    
    img.loaded {
        animation: fadeIn 0.5s ease;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    .loading-content {
        padding: 20px;
        text-align: center;
        color: #666;
        background: #f9f9f9;
        border-radius: 4px;
        animation: pulse 1.5s infinite;
    }
    
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.7; }
    }
    
    .error-content {
        padding: 20px;
        text-align: center;
        color: #e74c3c;
        background: #fdf2f2;
        border: 1px solid #f5c6cb;
        border-radius: 4px;
    }
    
    .student-item, .task-item {
        padding: 10px;
        border-bottom: 1px solid #eee;
        transition: background-color 0.2s ease;
    }
    
    .student-item:hover, .task-item:hover {
        background-color: #f8f9fa;
    }
`;

// Agregar estilos al documento
const styleSheet = document.createElement('style');
styleSheet.textContent = lazyLoadingStyles;
document.head.appendChild(styleSheet);

// Inicializar lazy loader global
window.lazyLoader = new LazyLoader();

// Re-observar elementos cuando se agregue contenido dinámico
const originalAppendChild = Element.prototype.appendChild;
Element.prototype.appendChild = function(child) {
    const result = originalAppendChild.call(this, child);
    if (window.lazyLoader && child.nodeType === 1) {
        window.lazyLoader.observe(child);
    }
    return result;
};