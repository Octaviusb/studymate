<?php
require_once 'config.php';

try {
    $pdo = getDBConnection();

    echo "<h1>Poblando StudyMate con Datos Básicos</h1>";
    echo "<pre>";

    // Crear 1 organización adicional
    echo "=== CREANDO ORGANIZACIÓN ===\n";
    $stmt = $pdo->prepare("INSERT IGNORE INTO organizations (name, status) VALUES (?, 'active')");
    $stmt->execute(['Colegio Demo']);
    echo "✓ Colegio Demo creada\n";

    // Crear algunos usuarios básicos
    echo "\n=== CREANDO USUARIOS ===\n";

    $users = [
        [1, 'admin_demo', 'admin@demo.com', 'Admin Demo', 'admin'],
        [1, 'teacher_demo', 'teacher@demo.com', 'Profesor Demo', 'teacher'],
        [1, 'student_demo', 'student@demo.com', 'Estudiante Demo', 'student'],
        [2, 'admin_colegio', 'admin@colegio.com', 'Admin Colegio', 'admin']
    ];

    foreach ($users as $user) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO users (organization_id, username, email, password_hash, full_name, role, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
        $stmt->execute([$user[0], $user[1], $user[2], password_hash('123456', PASSWORD_DEFAULT), $user[3], $user[4]]);
        echo "✓ {$user[3]} creado\n";
    }

    // Estadísticas finales
    echo "\n=== ESTADÍSTICAS ===\n";

    $stmt = $pdo->query("SELECT COUNT(*) FROM organizations");
    $totalOrgs = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $totalUsers = $stmt->fetchColumn();

    echo "✅ {$totalOrgs} organizaciones\n";
    echo "✅ {$totalUsers} usuarios\n";

    echo "\n🎉 DATOS BÁSICOS CREADOS\n";
    echo "Ahora puedes probar el dashboard de superadmin!\n";

} catch (Exception $e) {
    echo "<h1>Error</h1>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>