<?php
require_once 'config.php';

$email = 'espanol@demo.com';
$password = '123456';

try {
    $pdo = getDBConnection();
    
    // Verificar si existe el usuario
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h3>Debug Login Docente: $email</h3>";
    
    if (!$user) {
        echo "❌ Usuario NO existe<br>";
        
        // Crear docente demo
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $insertStmt = $pdo->prepare("INSERT INTO users (organization_id, username, email, password_hash, full_name, role, status) VALUES (1, 'espanol', ?, ?, 'Profesor Español', 'teacher', 'active')");
        $result = $insertStmt->execute([$email, $hash]);
        
        if ($result) {
            echo "✅ Docente creado con hash: $hash<br>";
        }
    } else {
        echo "✅ Usuario existe<br>";
        echo "Hash actual: " . $user['password_hash'] . "<br>";
        echo "Status: " . $user['status'] . "<br>";
        echo "Role: " . $user['role'] . "<br>";
        echo "Organization ID: " . $user['organization_id'] . "<br>";
        
        // Verificar password
        if (password_verify($password, $user['password_hash'])) {
            echo "✅ Password CORRECTO<br>";
        } else {
            echo "❌ Password INCORRECTO<br>";
            
            // Actualizar con nuevo hash
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $updateStmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
            $updateStmt->execute([$newHash, $email]);
            echo "✅ Password actualizado: $newHash<br>";
        }
    }
    
    // Probar login completo
    echo "<br><h4>Probando login API:</h4>";
    $loginStmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
    $loginStmt->execute([$email]);
    $loginUser = $loginStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($loginUser && password_verify($password, $loginUser['password_hash'])) {
        echo "✅ LOGIN API EXITOSO<br>";
        echo "Usuario: " . $loginUser['full_name'] . "<br>";
        echo "Rol: " . $loginUser['role'] . "<br>";
        echo "Org ID: " . $loginUser['organization_id'] . "<br>";
    } else {
        echo "❌ LOGIN API FALLÓ<br>";
        if (!$loginUser) {
            echo "- Usuario no encontrado o inactivo<br>";
        } else {
            echo "- Password no coincide<br>";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>