<?php
/**
 * StudyMate Setup Script
 * Ejecutar una sola vez para configurar la base de datos
 */

require_once 'config.php';

// Solo permitir ejecución en desarrollo o con clave especial
$setupKey = $_GET['key'] ?? '';
if ($setupKey !== 'studymate_setup_2024') {
    http_response_code(403);
    die('Acceso denegado. Proporciona la clave de setup correcta.');
}

try {
    $pdo = getDBConnection();
    
    echo "<h1>StudyMate Database Setup</h1>";
    echo "<pre>";
    
    // Leer y ejecutar el schema
    $schemaPath = __DIR__ . '/database/schema.sql';
    if (!file_exists($schemaPath)) {
        throw new Exception('Archivo schema.sql no encontrado');
    }
    
    $schema = file_get_contents($schemaPath);
    $statements = explode(';', $schema);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (empty($statement)) continue;
        
        try {
            $pdo->exec($statement);
            echo "✓ Ejecutado: " . substr($statement, 0, 50) . "...\n";
        } catch (PDOException $e) {
            echo "⚠ Error en: " . substr($statement, 0, 50) . "... - " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n=== SETUP COMPLETADO ===\n";
    echo "Base de datos configurada correctamente.\n";
    echo "Usuario admin creado con email: admin@studymate.com\n";
    echo "Contraseña por defecto: password\n";
    echo "\n⚠ IMPORTANTE: Elimina este archivo setup.php después del setup\n";
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<h1>Error en Setup</h1>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Verifica la configuración de la base de datos en config.php</p>";
}
?>