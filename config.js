// Configuración de API para XAMPP
window.API_BASE_URL = 'http://localhost/StudyMateSaaS/api';

// Sobrescribir fetch para interceptar llamadas a localhost:8000
const originalFetch = window.fetch;
window.fetch = function(url, options) {
    // Si la URL contiene localhost:8000, reemplazarla
    if (typeof url === 'string' && url.includes('localhost:8000')) {
        url = url.replace('http://localhost:8000', 'http://localhost/StudyMateSaaS');
    }
    
    return originalFetch(url, options);
};

console.log('✅ StudyMate API configurada para XAMPP:', window.API_BASE_URL);