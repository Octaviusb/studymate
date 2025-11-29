<?php
require_once 'config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h1>🔍 Debug Login - StudyMate SaaS</h1>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .error{color:red;} .success{color:green;} table{border-collapse:collapse;width:100%;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} th{background:#f2f2f2;}</style>";

try {
    $pdo = getDBConnection();
    echo "<div class='success'>✅ Conexión a base de datos exitosa</div><br>";

    // Verificar estructura de tabla users
    echo "<h2>📋 Estructura de tabla 'users':</h2>";
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<table><tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "<td>{$col['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table><br>";

    // Verificar usuarios existentes
    echo "<h2>👥 Usuarios en la base de datos:</h2>";
    $stmt = $pdo->query("SELECT id, username, email, password_hash, role, status, organization_id FROM users LIMIT 10");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($users)) {
        echo "<div class='error'>❌ No hay usuarios en la tabla 'users'</div><br>";
    } else {
        echo "<table><tr><th>ID</th><th>Username</th><th>Email</th><th>Password Hash</th><th>Role</th><th>Status</th><th>Org ID</th></tr>";
        foreach ($users as $user) {
            $passHash = substr($user['password_hash'], 0, 20) . '...';
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td>{$user['username']}</td>";
            echo "<td>{$user['email']}</td>";
            echo "<td>{$passHash}</td>";
            echo "<td>{$user['role']}</td>";
            echo "<td>{$user['status']}</td>";
            echo "<td>{$user['organization_id']}</td>";
            echo "</tr>";
        }
        echo "</table><br>";
    }

    // Verificar tabla organizations
    echo "<h2>🏢 Organizaciones:</h2>";
    $stmt = $pdo->query("SELECT id, name, email, status FROM organizations LIMIT 5");
    $orgs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($orgs)) {
        echo "<div class='error'>❌ No hay organizaciones en la tabla 'organizations'</div><br>";
    } else {
        echo "<table><tr><th>ID</th><th>Name</th><th>Email</th><th>Status</th></tr>";
        foreach ($orgs as $org) {
            echo "<tr>";
            echo "<td>{$org['id']}</td>";
            echo "<td>{$org['name']}</td>";
            echo "<td>{$org['email']}</td>";
            echo "<td>{$org['status']}</td>";
            echo "</tr>";
        }
        echo "</table><br>";
    }

    // Probar login con superadmin
    echo "<h2>🔐 Prueba de login con superadmin@studymate.com:</h2>";
    $testEmail = 'superadmin@studymate.com';
    $testPassword = '123456';

    $stmt = $pdo->prepare("
        SELECT u.id, u.username, u.email, u.password_hash, u.full_name, u.role, u.status, u.organization_id
        FROM users u
        WHERE u.email = ? AND u.status = 'active'
    ");
    $stmt->execute([$testEmail]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo "<div class='error'>❌ Usuario no encontrado o inactivo</div><br>";
    } else {
        echo "<div class='success'>✅ Usuario encontrado: {$user['email']} (Role: {$user['role']})</div>";

        if (password_verify($testPassword, $user['password_hash'])) {
            echo "<div class='success'>✅ Contraseña correcta</div>";
        } else {
            echo "<div class='error'>❌ Contraseña incorrecta</div>";
            echo "<div>Hash almacenado: " . substr($user['password_hash'], 0, 20) . "...</div>";
            echo "<div>Hash esperado: " . password_hash($testPassword, PASSWORD_DEFAULT) . "</div>";
        }
        echo "<br>";
    }

} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
    echo "<div class='error'>Código de error: " . $e->getCode() . "</div>";
}
?>