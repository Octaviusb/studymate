<?php
require_once 'config.php';

try {
    $pdo = getDBConnection();

    echo "🔧 Creando usuarios demo faltantes...<br><br>";

    $demoUsers = [
        [
            'email' => 'matematicas@demo.com',
            'username' => 'matematicas',
            'full_name' => 'Profesora María González',
            'role' => 'teacher'
        ],
        [
            'email' => 'espanol@demo.com',
            'username' => 'espanol',
            'full_name' => 'Profesor Carlos Rodríguez',
            'role' => 'teacher'
        ],
        [
            'email' => 'ana.garcia@demo.com',
            'username' => 'ana.garcia',
            'full_name' => 'Ana García',
            'role' => 'student',
            'grade_level' => '9°'
        ],
        [
            'email' => 'padre1@demo.com',
            'username' => 'padre1',
            'full_name' => 'Juan López',
            'role' => 'parent'
        ]
    ];

    $password = '123456';
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    foreach ($demoUsers as $userData) {
        // Eliminar usuario existente si existe
        $pdo->prepare("DELETE FROM users WHERE email = ?")->execute([$userData['email']]);

        // Crear nuevo usuario
        $stmt = $pdo->prepare("
            INSERT INTO users (organization_id, username, email, password_hash, full_name, role, grade_level, status)
            VALUES (1, ?, ?, ?, ?, ?, ?, 'active')
        ");
        $stmt->execute([
            $userData['username'],
            $userData['email'],
            $passwordHash,
            $userData['full_name'],
            $userData['role'],
            $userData['grade_level'] ?? null
        ]);

        echo "✅ Usuario {$userData['role']} creado: {$userData['email']}<br>";
    }

    echo "<br>🎉 Todos los usuarios demo han sido creados<br>";
    echo "<br><strong>Credenciales de acceso (contraseña: 123456):</strong><br>";
    echo "• Profesora Matemáticas: matematicas@demo.com<br>";
    echo "• Profesor Español: espanol@demo.com<br>";
    echo "• Estudiante Ana: ana.garcia@demo.com<br>";
    echo "• Padre: padre1@demo.com<br>";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>