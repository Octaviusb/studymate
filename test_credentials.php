<?php
require_once 'config.php';

$email = 'secretaria@demo.com';
$password = '123456';

try {
    $pdo = getDBConnection();
    
    // Verificar usuario actual
    $stmt = $pdo->prepare("SELECT email, password_hash FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "Usuario encontrado: " . $user['email'] . "\n";
        echo "Hash actual: " . $user['password_hash'] . "\n";
        
        // Verificar contraseña
        if (password_verify($password, $user['password_hash'])) {
            echo "✅ Contraseña CORRECTA\n";
        } else {
            echo "❌ Contraseña INCORRECTA\n";
            
            // Generar nuevo hash
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            echo "Nuevo hash: " . $newHash . "\n";
            
            // Actualizar
            $updateStmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
            $result = $updateStmt->execute([$newHash, $email]);
            
            if ($result) {
                echo "✅ Hash actualizado\n";
            }
        }
    } else {
        echo "❌ Usuario no encontrado\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>