<?php
require_once 'config.php';

try {
    $pdo = getDBConnection();
    
    // Verificar coordinador
    $stmt = $pdo->prepare("SELECT id, email, role, status FROM users WHERE email = 'coordinador@demo.studymate.com'");
    $stmt->execute();
    $coordinator = $stmt->fetch();
    
    if ($coordinator) {
        echo "✅ Coordinador encontrado:<br>";
        echo "ID: " . $coordinator['id'] . "<br>";
        echo "Email: " . $coordinator['email'] . "<br>";
        echo "Rol: " . $coordinator['role'] . "<br>";
        echo "Estado: " . $coordinator['status'] . "<br><br>";
        
        // Verificar contraseña
        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE email = 'coordinador@demo.studymate.com'");
        $stmt->execute();
        $hash = $stmt->fetchColumn();
        
        if (password_verify('123456', $hash)) {
            echo "✅ Contraseña correcta<br>";
        } else {
            echo "❌ Contraseña incorrecta<br>";
            
            // Actualizar contraseña
            $newHash = password_hash('123456', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE email = 'coordinador@demo.studymate.com'");
            $stmt->execute([$newHash]);
            echo "✅ Contraseña actualizada<br>";
        }
    } else {
        echo "❌ Coordinador no encontrado<br>";
        
        // Crear coordinador
        $password = password_hash('123456', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO users (organization_id, username, email, password_hash, full_name, role, status) 
            VALUES (1, 'coordinador', 'coordinador@demo.studymate.com', ?, 'Coordinador Demo', 'coordinator', 'active')
        ");
        $stmt->execute([$password]);
        echo "✅ Coordinador creado<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>