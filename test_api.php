<?php
/**
 * StudyMate API Test Script
 * Pruebas básicas de los endpoints
 */

// Solo permitir en desarrollo
if (!isset($_GET['test']) || $_GET['test'] !== 'studymate') {
    http_response_code(403);
    die('Acceso denegado');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>StudyMate API Tests</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test { margin: 10px 0; padding: 10px; border: 1px solid #ccc; }
        .success { background: #d4edda; }
        .error { background: #f8d7da; }
        button { padding: 5px 10px; margin: 5px; }
    </style>
</head>
<body>
    <h1>StudyMate API Tests</h1>
    
    <div class="test">
        <h3>Test 1: API Status</h3>
        <button onclick="testAPI()">Test API</button>
        <div id="api-result"></div>
    </div>
    
    <div class="test">
        <h3>Test 2: User Registration</h3>
        <button onclick="testRegister()">Test Register</button>
        <div id="register-result"></div>
    </div>
    
    <div class="test">
        <h3>Test 3: User Login</h3>
        <button onclick="testLogin()">Test Login</button>
        <div id="login-result"></div>
    </div>
    
    <div class="test">
        <h3>Test 4: Chat Message</h3>
        <button onclick="testChat()">Test Chat</button>
        <div id="chat-result"></div>
    </div>
    
    <div class="test">
        <h3>Test 5: Create Class (Teacher)</h3>
        <button onclick="testCreateClass()">Test Create Class</button>
        <div id="class-result"></div>
    </div>
    
    <div class="test">
        <h3>Test 6: Organization Stats</h3>
        <button onclick="testOrgStats()">Test Org Stats</button>
        <div id="stats-result"></div>
    </div>

    <script>
        async function testAPI() {
            try {
                const response = await fetch('/api/');
                const data = await response.json();
                document.getElementById('api-result').innerHTML = 
                    `<div class="success">✓ API funcionando: ${data.message}</div>`;
            } catch (error) {
                document.getElementById('api-result').innerHTML = 
                    `<div class="error">✗ Error: ${error.message}</div>`;
            }
        }
        
        async function testRegister() {
            try {
                const response = await fetch('/api/auth/register', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        name: 'Test User',
                        email: 'test@studymate.com',
                        password: 'test123',
                        role: 'student',
                        organization_id: 1
                    })
                });
                const data = await response.json();
                document.getElementById('register-result').innerHTML = 
                    `<div class="${response.ok ? 'success' : 'error'}">
                        ${response.ok ? '✓' : '✗'} ${data.message || 'Registro completado'}
                    </div>`;
            } catch (error) {
                document.getElementById('register-result').innerHTML = 
                    `<div class="error">✗ Error: ${error.message}</div>`;
            }
        }
        
        async function testLogin() {
            try {
                const response = await fetch('/api/auth/login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        email: 'test@studymate.com',
                        password: 'test123'
                    })
                });
                const data = await response.json();
                document.getElementById('login-result').innerHTML = 
                    `<div class="${response.ok ? 'success' : 'error'}">
                        ${response.ok ? '✓' : '✗'} ${data.message || 'Login exitoso'}
                    </div>`;
                
                if (response.ok && data.token) {
                    localStorage.setItem('studymate_token', data.token);
                    localStorage.setItem('studymate_user', JSON.stringify(data.user));
                }
            } catch (error) {
                document.getElementById('login-result').innerHTML = 
                    `<div class="error">✗ Error: ${error.message}</div>`;
            }
        }
        
        async function testChat() {
            const user = JSON.parse(localStorage.getItem('studymate_user') || '{}');
            if (!user.id) {
                document.getElementById('chat-result').innerHTML = 
                    '<div class="error">✗ Debes hacer login primero</div>';
                return;
            }
            
            try {
                const response = await fetch('/api/chat', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        message: 'Hola, esto es una prueba',
                        sessionId: 'test_session_123',
                        userId: user.id
                    })
                });
                const data = await response.json();
                document.getElementById('chat-result').innerHTML = 
                    `<div class="${response.ok ? 'success' : 'error'}">
                        ${response.ok ? '✓' : '✗'} Chat: ${data.message}
                    </div>`;
            } catch (error) {
                document.getElementById('chat-result').innerHTML = 
                    `<div class="error">✗ Error: ${error.message}</div>`;
            }
        }
        
        async function testCreateClass() {
            const user = JSON.parse(localStorage.getItem('studymate_user') || '{}');
            if (!user.id) {
                document.getElementById('class-result').innerHTML = 
                    '<div class="error">✗ Debes hacer login primero</div>';
                return;
            }
            
            try {
                const response = await fetch('/api/classes', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        name: 'Matemáticas Test',
                        subject: 'Matemáticas',
                        grade_level: '11°',
                        description: 'Clase de prueba',
                        user_id: user.id
                    })
                });
                const data = await response.json();
                document.getElementById('class-result').innerHTML = 
                    `<div class="${response.ok ? 'success' : 'error'}">
                        ${response.ok ? '✓' : '✗'} Clase: ${data.message || data.name || 'Creada'}
                    </div>`;
            } catch (error) {
                document.getElementById('class-result').innerHTML = 
                    `<div class="error">✗ Error: ${error.message}</div>`;
            }
        }
        
        async function testOrgStats() {
            const user = JSON.parse(localStorage.getItem('studymate_user') || '{}');
            if (!user.id) {
                document.getElementById('stats-result').innerHTML = 
                    '<div class="error">✗ Debes hacer login primero</div>';
                return;
            }
            
            try {
                const response = await fetch(`/api/organizations/stats?user_id=${user.id}`);
                const data = await response.json();
                document.getElementById('stats-result').innerHTML = 
                    `<div class="${response.ok ? 'success' : 'error'}">
                        ${response.ok ? '✓' : '✗'} Stats: ${JSON.stringify(data, null, 2)}
                    </div>`;
            } catch (error) {
                document.getElementById('stats-result').innerHTML = 
                    `<div class="error">✗ Error: ${error.message}</div>`;
            }
        }
    </script>
</body>
</html>