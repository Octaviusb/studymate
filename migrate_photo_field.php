<?php
require_once 'config.php';

try {
    $pdo = getDBConnection();
    
    // Verificar si el campo ya existe
    $stmt = $pdo->query("SHOW COLUMNS FROM user_profiles LIKE 'profile_photo'");
    if ($stmt->rowCount() == 0) {
        // Agregar el campo
        $pdo->exec("ALTER TABLE user_profiles ADD COLUMN profile_photo VARCHAR(255) NULL AFTER medical_conditions");
        echo "✅ Campo profile_photo agregado exitosamente\n";
    } else {
        echo "ℹ️ El campo profile_photo ya existe\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>