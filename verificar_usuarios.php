<?php
require_once 'config.php';

try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT id, username, email, full_name, role, status FROM users ORDER BY role, email");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Usuarios en la Base de Datos:</h2>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Nombre</th><th>Rol</th><th>Estado</th></tr>";
    
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>{$user['id']}</td>";
        echo "<td>{$user['username']}</td>";
        echo "<td>{$user['email']}</td>";
        echo "<td>{$user['full_name']}</td>";
        echo "<td>{$user['role']}</td>";
        echo "<td>{$user['status']}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>