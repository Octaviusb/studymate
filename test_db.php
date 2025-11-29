<?php
require_once 'config.php';

try {
    $pdo = getDBConnection();
    echo "✅ Conexión exitosa a la base de datos<br>";
    
    // Verificar tablas existentes
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h3>Tablas encontradas:</h3>";
    foreach($tables as $table) {
        echo "- $table<br>";
    }
    
} catch(Exception $e) {
    echo "❌ Error de conexión: " . $e->getMessage();
}
?>