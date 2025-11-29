<?php
// Test de conexión a la base de datos
require_once 'config.php';

try {
    $pdo = getDBConnection();
    echo "✅ Conexión a base de datos exitosa\n";
    
    // Verificar si las tablas existen
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "⚠️ Base de datos vacía - ejecutar migración\n";
    } else {
        echo "✅ Tablas encontradas: " . implode(', ', $tables) . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>