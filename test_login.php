<?php
require_once 'config.php';

$email = 'superadmin@studymate.com';
$password = '123456';

try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT id, username, email, password_hash, full_name, role FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h2>Test de Login para: $email</h2>";
    
    if ($user) {
        echo "<p><strong>Usuario encontrado:</strong></p>";
        echo "<pre>" . print_r($user, true) . "</pre>";
        
        echo "<p><strong>Hash en BD:</strong> " . $user['password_hash'] . "</p>";
        
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        echo "<p><strong>Nuevo hash generado:</strong> $newHash</p>";
        
        $verify1 = password_verify($password, $user['password_hash']);
        echo "<p><strong>Verificación con hash actual:</strong> " . ($verify1 ? "✅ CORRECTO" : "❌ INCORRECTO") . "</p>";
        
        $verify2 = password_verify($password, $newHash);
        echo "<p><strong>Verificación con hash nuevo:</strong> " . ($verify2 ? "✅ CORRECTO" : "❌ INCORRECTO") . "</p>";
        
        if (!$verify1) {
            echo "<h3>SQL para corregir:</h3>";
            echo "<code>UPDATE users SET password_hash = '$newHash' WHERE email = '$email';</code>";
        }
        
    } else {
        echo "<p><strong>❌ Usuario NO encontrado</strong></p>";
        echo "<h3>SQL para crear usuario:</h3>";
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        echo "<code>INSERT INTO users (organization_id, username, email, password_hash, full_name, role, status) VALUES (1, 'superadmin', '$email', '$newHash', 'Super Administrador', 'super_admin', 'active');</code>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>