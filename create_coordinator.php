<?php
require_once 'config.php';

try {
    $pdo = getDBConnection();
    
    // Crear usuario coordinador
    $email = 'coordinador@demo.studymate.com';
    $password = '123456';
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    
    // Verificar si ya existe
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        echo "El coordinador ya existe. Actualizando contraseña...<br>";
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
        $stmt->execute([$passwordHash, $email]);
        echo "Contraseña actualizada correctamente.<br>";
    } else {
        echo "Creando nuevo coordinador...<br>";
        $stmt = $pdo->prepare("
            INSERT INTO users (organization_id, username, email, password_hash, full_name, role, status) 
            VALUES (1, 'coordinador', ?, ?, 'Coordinador Demo', 'coordinator', 'active')
        ");
        $stmt->execute([$email, $passwordHash]);
        echo "Coordinador creado correctamente.<br>";
    }
    
    echo "<h3>Credenciales del Coordinador:</h3>";
    echo "Email: <strong>coordinador@demo.studymate.com</strong><br>";
    echo "Contraseña: <strong>123456</strong><br>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>