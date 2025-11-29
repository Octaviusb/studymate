<?php
// Configuración específica para Azure App Service

// Configuración de la base de datos para Azure SQL
if (getenv('MYSQL_SSL_CA')) {
    // Si estás usando Azure Database for MySQL con SSL
    $ssl_ca = base64_decode(getenv('MYSQL_SSL_CA'));
    file_put_contents('/tmp/ssl_cert.pem', $ssl_ca);
    
    $pdo_options = [
        PDO::MYSQL_ATTR_SSL_CA => '/tmp/ssl_cert.pem',
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4'
    ];
} else {
    // Configuración estándar para SQL Server
    $pdo_options = [
        PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::SQLSRV_ATTR_QUERY_TIMEOUT => 30
    ];
}

// Configuración de almacenamiento en Azure
if (getenv('APPSETTING_WEBSITE_SITE_NAME')) {
    // Usar Azure Blob Storage para almacenamiento
    define('STORAGE_TYPE', 'azure');
    define('AZURE_STORAGE_CONNECTION_STRING', getenv('AZURE_STORAGE_CONNECTION_STRING'));
    define('AZURE_STORAGE_CONTAINER', 'studymate-files');
} else {
    // Usar almacenamiento local
    define('STORAGE_TYPE', 'local');
    define('UPLOAD_DIR', '/home/site/wwwroot/uploads');
}

// Configuración de correo
if (getenv('SENDGRID_API_KEY')) {
    define('MAIL_DRIVER', 'sendgrid');
    define('SENDGRID_API_KEY', getenv('SENDGRID_API_KEY'));
} else {
    define('MAIL_DRIVER', 'smtp');
    define('MAIL_HOST', 'smtp.sendgrid.net');
    define('MAIL_PORT', 587);
    define('MAIL_USERNAME', 'apikey');
    define('MAIL_PASSWORD', getenv('SENDGRID_API_KEY'));
    define('MAIL_ENCRYPTION', 'tls');
}

// Configuración de CORS para Azure Front Door/CDN
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization');

// Configuración de la sesión para Azure
ini_set('session.save_handler', 'redis');
ini_set('session.save_path', 'tcp://'.getenv('REDIS_HOST').':'.getenv('REDIS_PORT'));

// Habilitar compresión GZIP
if (!headers_sent() && extension_loaded('zlib') && !ini_get('zlib.output_compression')) {
    ob_start('ob_gzhandler');
}

// Configuración de caché
if (getenv('REDIS_HOST')) {
    define('CACHE_DRIVER', 'redis');
    define('REDIS_HOST', getenv('REDIS_HOST'));
    define('REDIS_PORT', getenv('REDIS_PORT') ?: '6379');
    define('REDIS_PASSWORD', getenv('REDIS_PASSWORD') ?: null);
} else {
    define('CACHE_DRIVER', 'file');
}

// Configuración de monitoreo
if (getenv('APPINSIGHTS_INSTRUMENTATIONKEY')) {
    define('APP_INSIGHTS_KEY', getenv('APPINSIGHTS_INSTRUMENTATIONKEY'));
}

// Configuración de la URL base
$request_uri = $_SERVER['REQUEST_URI'] ?? '/';
$script_name = $_SERVER['SCRIPT_NAME'] ?? 'index.php';
$base_path = str_replace(basename($script_name), '', $script_name);

define('BASE_URL', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . "://" . $_SERVER['HTTP_HOST'] . $base_path);

// Incluir el archivo de configuración principal
require_once __DIR__ . '/config.php';

// Sobrescribir configuración de base de datos si está en Azure
if (getenv('MYSQLCONNSTR_defaultConnection')) {
    // Formato: Database=dbname;Data Source=host:port;User Id=user;Password=pass
    $db_str = getenv('MYSQLCONNSTR_defaultConnection');
    $db = [];
    foreach (explode(';', $db_str) as $pair) {
        list($key, $value) = explode('=', $pair, 2);
        $db[trim($key)] = trim($value);
    }
    
    define('DB_HOST', $db['Data Source']);
    define('DB_NAME', $db['Database']);
    define('DB_USER', $db['User Id']);
    define('DB_PASS', $db['Password']);
}

// Configuración de errores para producción
if (getenv('APP_ENV') === 'production') {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_NOTICE);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', __DIR__ . '/storage/logs/error.log');
}
