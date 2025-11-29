<?php
// Simple CORS test endpoint
header('Content-Type: application/json');

// CORS headers
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

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

echo json_encode([
    'success' => true,
    'message' => 'CORS test successful',
    'origin' => $origin,
    'method' => $_SERVER['REQUEST_METHOD'],
    'timestamp' => date('Y-m-d H:i:s')
]);
?>