<?php
// ===============================
// CONFIGURACIÓN STUDYMATE SAAS - PRODUCCIÓN
// ===============================

// 🔒 Seguridad - PRODUCCIÓN
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// 🔑 JWT Secret
define('JWT_SECRET', 'studymate_secret_key_2024');

// 🔑 API Key de Google Gemini
define('GEMINI_API_KEY', 'AIzaSyB4Po08k9_bHstWF3wNTeIShz8FWvajac4');

// 🔑 API Key de OpenAI
define('OPENAI_API_KEY', 'getenv('OPENAI_API_KEY')');

// 📧 Correo del administrador
define('ADMIN_EMAIL', 'admin@studymate.com');

// 🤖 AI Configuration
define('AI_PROVIDER', 'openai');
define('AI_MODEL', 'gpt-3.5-turbo');

// 🌐 SaaS Settings
define('SAAS_DOMAIN', 'studymate.com');
define('ENABLE_MULTI_TENANT', true);
define('SAAS_MODE', true);

// ===============================
// BASE DE DATOS - INFINITYFREE
// ===============================
define('DB_HOST', 'sql309.infinityfree.com');
define('DB_NAME', 'if0_39306959_studymate');
define('DB_USER', 'if0_39306959');
define('DB_PASS', 'Eneroctavio19');

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
        // Log error instead of showing it
        error_log("Database connection error: " . $e->getMessage());
        
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
