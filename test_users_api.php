<?php
require_once 'config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h2>Test Users API</h2>";

try {
    $pdo = getDBConnection();
    
    // Test 1: Verificar conexión
    echo "<h3>✅ Conexión a BD exitosa</h3>";
    
    // Test 2: Verificar usuarios
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $userCount = $stmt->fetchColumn();
    echo "<p>Total usuarios en BD: <strong>$userCount</strong></p>";
    
    // Test 3: Simular la consulta de la API
    $orgId = 1;
    $role = 'student';
    
    $sql = "SELECT id, username, email, full_name, role, grade_level, status, created_at FROM users WHERE organization_id = ?";
    $params = [$orgId];
    
    if ($role) {
        $sql .= " AND role = ?";
        $params[] = $role;
    }
    
    $sql .= " ORDER BY full_name ASC";
    
    echo "<h3>Consulta SQL:</h3>";
    echo "<code>$sql</code><br>";
    echo "<strong>Parámetros:</strong> " . implode(', ', $params) . "<br><br>";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Resultados:</h3>";
    echo "<p>Estudiantes encontrados: <strong>" . count($users) . "</strong></p>";
    
    if (count($users) > 0) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Nombre</th><th>Email</th><th>Rol</th><th>Grado</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>" . $user['id'] . "</td>";
            echo "<td>" . $user['full_name'] . "</td>";
            echo "<td>" . $user['email'] . "</td>";
            echo "<td>" . $user['role'] . "</td>";
            echo "<td>" . ($user['grade_level'] ?? 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Test 4: Simular respuesta JSON
    echo "<h3>Respuesta JSON:</h3>";
    $response = ['success' => true, 'users' => $users];
    echo "<pre>" . json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
    
} catch (Exception $e) {
    echo "<h3>❌ Error:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>