<?php
require_once 'config.php';

try {
    $pdo = getDBConnection();

    echo "🔧 Creando usuario secretaria...<br><br>";

    $email = 'secretaria@demo.studymate.com';
    $password = '123456';
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // Eliminar secretaria existente si existe
    $pdo->prepare("DELETE FROM users WHERE email = ?")->execute([$email]);

    // Crear nueva secretaria
    $stmt = $pdo->prepare("
        INSERT INTO users (organization_id, username, email, password_hash, full_name, role, status)
        VALUES (1, 'secretaria', ?, ?, 'Secretaria Demo', 'secretary', 'active')
    ");
    $stmt->execute([$email, $passwordHash]);

    echo "✅ Secretaria creada correctamente<br><br>";
    echo "<strong>Credenciales:</strong><br>";
    echo "Email: secretaria@demo.studymate.com<br>";
    echo "Contraseña: 123456<br><br>";

    // Verificar que se creó correctamente
    $stmt = $pdo->prepare("SELECT id, email, role, status FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        echo "✅ Verificación exitosa:<br>";
        echo "ID: " . $user['id'] . "<br>";
        echo "Email: " . $user['email'] . "<br>";
        echo "Rol: " . $user['role'] . "<br>";
        echo "Estado: " . $user['status'] . "<br>";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>