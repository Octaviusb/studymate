<?php
require_once 'config.php';

echo "<h1>🧪 Test Simple StudyMate SaaS</h1>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .error{color:red;} .warning{color:orange;}</style>";

try {
    $pdo = getDBConnection();
    echo "<div class='success'>✅ Conexión a BD exitosa</div><br>";

    // Test usuarios
    echo "<h2>👥 Usuarios:</h2>";
    $users = [
        'superadmin@studymate.com',
        'admin@studymate.com',
        'coordinador@demo.studymate.com',
        'secretaria@demo.studymate.com',
        'matematicas@demo.com',
        'espanol@demo.com',
        'ana.garcia@demo.com',
        'padre1@demo.com'
    ];

    foreach ($users as $email) {
        $stmt = $pdo->prepare("SELECT id, role, status FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && $user['status'] === 'active') {
            echo "<div class='success'>✅ {$email} ({$user['role']})</div>";
        } else {
            echo "<div class='error'>❌ {$email} - No encontrado o inactivo</div>";
        }
    }

    // Test tablas
    echo "<br><h2>📊 Tablas:</h2>";
    $tables = ['organizations', 'subjects', 'tasks', 'task_submissions'];
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM `{$table}`");
            $count = $stmt->fetchColumn();
            echo "<div class='success'>✅ {$table}: {$count} registros</div>";
        } catch (Exception $e) {
            echo "<div class='error'>❌ {$table}: Error - {$e->getMessage()}</div>";
        }
    }

    // Test login
    echo "<br><h2>🔐 Test Login:</h2>";
    $testUser = 'superadmin@studymate.com';
    $testPass = '123456';

    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE email = ? AND status = 'active'");
    $stmt->execute([$testUser]);
    $hash = $stmt->fetchColumn();

    if ($hash && password_verify($testPass, $hash)) {
        echo "<div class='success'>✅ Login superadmin funciona</div>";
    } else {
        echo "<div class='error'>❌ Login superadmin falla</div>";
    }

    echo "<br><h2>📋 Estado General:</h2>";
    echo "<div class='success'>✅ Sistema básico operativo</div>";
    echo "<div class='warning'>⚠️ Algunas funcionalidades avanzadas pendientes</div>";

} catch (Exception $e) {
    echo "<div class='error'>❌ Error general: " . $e->getMessage() . "</div>";
}
?>