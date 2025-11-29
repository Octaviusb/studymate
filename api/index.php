<?php
$uri = $_SERVER['REQUEST_URI'];
$base_path = '/api';

if (strpos($uri, $base_path) === 0) {
    // API request - handle as before
    header('Content-Type: application/json');
    
    // CORS headers for development
    $allowedOrigins = [
        'http://localhost:3000', 
        'http://127.0.0.1:5500', 
        'http://localhost:5500',
        'https://studymate.gt.tc'
    ];
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if (in_array($origin, $allowedOrigins)) {
        header('Access-Control-Allow-Origin: ' . $origin);
    } else {
        header('Access-Control-Allow-Origin: *');
    }
    
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Access-Control-Allow-Credentials: true');

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        exit(0);
    }

    // Include configuration
    require_once __DIR__ . '/../config.php';

    $routeParam = isset($_GET['route']) ? $_GET['route'] : null;
    if ($routeParam) {
        $path = $routeParam;
    } else {
        $uri_parts = explode('?', $uri);
        $path = substr($uri_parts[0], strlen($base_path));
        if (strpos($path, '/index.php') === 0) {
            $path = substr($path, strlen('/index.php'));
        }
    }

    if ($path === '') {
        $path = '/';
    }

    // Route the request
    if ($path === '/' || $path === '') {
        echo json_encode([
            'message' => 'StudyMate PHP API funcionando',
            'version' => '1.0',
            'features' => ['chat-inteligente', 'bienestar-emocional', 'organizacion-academica']
        ]);
    } elseif (strpos($path, '/auth') === 0) {
        require_once __DIR__ . '/routes/auth.php';
    } elseif (strpos($path, '/tasks') === 0) {
        require_once __DIR__ . '/routes/tasks.php';
    } elseif (strpos($path, '/wellness') === 0) {
        require_once __DIR__ . '/routes/wellness.php';
    } elseif (strpos($path, '/chat') === 0) {
        require_once __DIR__ . '/routes/chat.php';
    } elseif (strpos($path, '/classes') === 0) {
        require_once __DIR__ . '/routes/classes.php';
    } elseif (strpos($path, '/organizations') === 0) {
        require_once __DIR__ . '/routes/organizations.php';
    } elseif (strpos($path, '/subjects') === 0) {
        require_once __DIR__ . '/routes/subjects.php';
    } elseif (strpos($path, '/grades') === 0) {
        require_once __DIR__ . '/routes/grades.php';
    } elseif (strpos($path, '/achievements') === 0) {
        require_once __DIR__ . '/routes/achievements.php';
    } elseif (strpos($path, '/student-grades') === 0) {
        require_once __DIR__ . '/routes/student-grades.php';
    } elseif (strpos($path, '/reports') === 0) {
        require_once __DIR__ . '/routes/reports.php';
    } else {
        http_response_code(404);
        echo json_encode(['message' => 'Endpoint no encontrado']);
    }
} else {
    // Non-API request - serve frontend index.html
    $indexPath = dirname(__DIR__) . '/index.html';
    if (file_exists($indexPath) && is_readable($indexPath)) {
        header('Content-Type: text/html; charset=utf-8');
        readfile($indexPath);
    } else {
        http_response_code(404);
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html><head><title>404</title></head><body><h1>Page Not Found</h1></body></html>';
    }
}
?>
