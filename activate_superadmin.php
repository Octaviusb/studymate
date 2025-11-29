<?php
require_once 'config.php';

try {
    $pdo = getDBConnection();
    
    // Activar super admin
    $stmt = $pdo->prepare("UPDATE users SET status = 'active' WHERE email = 'admin@studymate.com'");
    $stmt->execute();
    
    echo "<h1>Super Admin Activado</h1>";
    echo "<p>✅ Usuario admin@studymate.com activado correctamente</p>";
    echo "<p><strong>Credenciales:</strong></p>";
    echo "<ul>";
    echo "<li>Email: admin@studymate.com</li>";
    echo "<li>Contraseña: 123456</li>";
    echo "<li>Rol: super_admin</li>";
    echo "</ul>";
    echo "<p><a href='login.html'>Ir al Login</a></p>";
    echo "<p><a href='dashboard_saas.html'>Dashboard SaaS (después del login)</a></p>";
    
} catch (Exception $e) {
    echo "<h1>Error</h1>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>