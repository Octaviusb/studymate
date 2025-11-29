<?php
require_once 'config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h1>🧪 Test Login API - StudyMate SaaS</h1>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .error{color:red;} .success{color:green;} pre{background:#f4f4f4;padding:10px;border-radius:4px;overflow-x:auto;}</style>";

$testUsers = [
    ['email' => 'superadmin@studymate.com', 'password' => '123456'],
    ['email' => 'admin@studymate.com', 'password' => '123456'],
    ['email' => 'coordinador@demo.studymate.com', 'password' => '123456']
];

foreach ($testUsers as $testUser) {
    echo "<h2>🔐 Probando login: {$testUser['email']}</h2>";

    // Simular la petición POST
    $_POST = $testUser;

    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            SELECT u.id, u.username, u.email, u.password_hash, u.full_name, u.role, u.grade_level, u.organization_id, o.name as organization_name
            FROM users u
            LEFT JOIN organizations o ON u.organization_id = o.id
            WHERE u.email = ? AND u.status = 'active'
        ");
        $stmt->execute([$testUser['email']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            echo "<div class='error'>❌ Usuario no encontrado</div>";
            continue;
        }

        echo "<div class='success'>✅ Usuario encontrado: {$user['email']} (Role: {$user['role']})</div>";

        if (password_verify($testUser['password'], $user['password_hash'])) {
            echo "<div class='success'>✅ Contraseña correcta</div>";

            $tokenData = [
                'userId' => $user['id'],
                'email' => $user['email'],
                'role' => $user['role'] ?? 'student',
                'exp' => time() + (24 * 60 * 60)
            ];
            $token = base64_encode(json_encode($tokenData));

            $userData = [
                'id' => $user['id'],
                'name' => $user['full_name'],
                'email' => $user['email'],
                'role' => $user['role'] ?? 'student',
                'organization_id' => $user['organization_id'],
                'organization_name' => $user['organization_name']
            ];

            echo "<div class='success'>✅ Login exitoso - Token generado</div>";
            echo "<strong>Respuesta JSON:</strong><br>";
            echo "<pre>" . json_encode([
                'token' => $token,
                'user' => $userData
            ], JSON_PRETTY_PRINT) . "</pre>";

        } else {
            echo "<div class='error'>❌ Contraseña incorrecta</div>";
            echo "<div>Hash en BD: " . substr($user['password_hash'], 0, 20) . "...</div>";
        }

    } catch (Exception $e) {
        echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
    }

    echo "<hr>";
}

echo "<h2>📝 Instrucciones para debugging:</h2>";
echo "<ol>";
echo "<li>Si todos los tests pasan, el problema está en el frontend (login.html)</li>";
echo "<li>Si algún test falla, hay un problema en la base de datos</li>";
echo "<li>Verifica que estés enviando los datos correctos desde el frontend</li>";
echo "</ol>";
?>