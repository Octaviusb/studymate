<?php
/**
 * StudyMate Development Configuration
 * Usar solo en desarrollo local
 */

// Activar modo desarrollo
define('DEVELOPMENT_MODE', true);

// Configuración de desarrollo
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Base de datos local (ajustar según tu configuración)
define('DB_HOST_DEV', 'localhost');
define('DB_NAME_DEV', 'studymate_dev');
define('DB_USER_DEV', 'root');
define('DB_PASS_DEV', '');

// API Keys de desarrollo
define('GEMINI_API_KEY_DEV', 'your_dev_gemini_api_key_here');

// CORS permisivo para desarrollo
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Función de conexión para desarrollo
function getDevDBConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST_DEV . ";dbname=" . DB_NAME_DEV . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        
        return new PDO($dsn, DB_USER_DEV, DB_PASS_DEV, $options);
    } catch (PDOException $e) {
        die('Development DB Error: ' . $e->getMessage());
    }
}

echo "<!-- Development Mode Active -->\n";
?>