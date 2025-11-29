<?php
// ===============================
// CONFIGURACIÓN STUDYMATE SAAS
// ===============================

// 🔒 Seguridad
error_reporting(E_ALL);
ini_set('display_errors', 0); // Desactivar en producción

// Cargar variables de entorno
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (!array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// Configuración de la aplicación
if (!defined('JWT_SECRET')) {
    define('JWT_SECRET', getenv('JWT_SECRET') ?: 'cambia_esta_clave_por_una_muy_segura_y_aleatoria');
}

if (!defined('API_ADMIN_KEY')) {
    define('API_ADMIN_KEY', getenv('API_ADMIN_KEY') ?: 'cambia_esta_clave');
}

// Configuración de APIs
define('GEMINI_API_KEY', getenv('GEMINI_API_KEY') ?: '');
define('OPENAI_API_KEY', getenv('OPENAI_API_KEY') ?: '');

define('ADMIN_EMAIL', getenv('ADMIN_EMAIL') ?: 'admin@studymate.com');

// Configuración de IA
define('AI_PROVIDER', getenv('AI_PROVIDER') ?: 'openai');
define('AI_MODEL', getenv('AI_MODEL') ?: 'gpt-3.5-turbo');

// Configuración SaaS
define('SAAS_DOMAIN', getenv('SAAS_DOMAIN') ?: 'studymate.com');
define('ENABLE_MULTI_TENANT', filter_var(getenv('ENABLE_MULTI_TENANT') ?: 'true', FILTER_VALIDATE_BOOLEAN));
define('SAAS_MODE', filter_var(getenv('SAAS_MODE') ?: 'true', FILTER_VALIDATE_BOOLEAN));

// Configuración de la base de datos
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'studymate');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_PORT', getenv('DB_PORT') ?: 3306);

// ===============================
// FUNCIÓN CONEXIÓN PDO
// ===============================
function getDBConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        if (!headers_sent()) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Database connection error'
            ]);
        }
        exit;
    }
}
?>
