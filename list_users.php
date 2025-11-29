<?php
require_once 'config.php';

echo "<h1>StudyMate SaaS - Lista de Usuarios</h1>\n";

try {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("
        SELECT u.id, u.username, u.email, u.full_name, u.role, u.grade_level, u.status, o.name as organization_name
        FROM users u 
        LEFT JOIN organizations o ON u.organization_id = o.id
        ORDER BY u.role, u.full_name
    ");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Usuarios Registrados en el Sistema</h2>\n";
    echo "<p><strong>Contraseña para todos los usuarios:</strong> <code>123456</code></p>\n";
    
    echo "<table border='1' cellpadding='10' cellspacing='0' style='border-collapse: collapse; width: 100%;'>\n";
    echo "<thead style='background: #f0f0f0;'>\n";
    echo "<tr><th>ID</th><th>Nombre Completo</th><th>Email</th><th>Rol</th><th>Grado</th><th>Organización</th><th>Estado</th></tr>\n";
    echo "</thead>\n";
    echo "<tbody>\n";
    
    foreach ($users as $user) {
        $roleColor = [
            'super_admin' => '#e74c3c',
            'admin' => '#f39c12', 
            'teacher' => '#3498db',
            'student' => '#27ae60'
        ];
        
        $color = $roleColor[$user['role']] ?? '#666';
        
        echo "<tr>\n";
        echo "<td>{$user['id']}</td>\n";
        echo "<td><strong>{$user['full_name']}</strong></td>\n";
        echo "<td>{$user['email']}</td>\n";
        echo "<td style='color: $color; font-weight: bold;'>" . strtoupper($user['role']) . "</td>\n";
        echo "<td>" . ($user['grade_level'] ?? 'N/A') . "</td>\n";
        echo "<td>{$user['organization_name']}</td>\n";
        echo "<td>{$user['status']}</td>\n";
        echo "</tr>\n";
    }
    
    echo "</tbody>\n";
    echo "</table>\n";
    
    // Resumen por rol
    echo "<h2>Resumen por Rol</h2>\n";
    $stmt = $pdo->prepare("SELECT role, COUNT(*) as count FROM users GROUP BY role ORDER BY role");
    $stmt->execute();
    $roleCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<ul>\n";
    foreach ($roleCounts as $roleCount) {
        echo "<li><strong>" . strtoupper($roleCount['role']) . ":</strong> {$roleCount['count']} usuarios</li>\n";
    }
    echo "</ul>\n";
    
    echo "<h2>Accesos Rápidos</h2>\n";
    echo "<ul>\n";
    echo "<li><a href='login.html'>Iniciar Sesión</a></li>\n";
    echo "<li><a href='academic_admin.html'>Panel Administración Académica</a></li>\n";
    echo "<li><a href='teacher_grades.html'>Panel Calificaciones Docentes</a></li>\n";
    echo "<li><a href='dashboard_saas.html'>Dashboard SaaS</a></li>\n";
    echo "</ul>\n";
    
} catch (Exception $e) {
    echo "<h2>❌ Error</h2>\n";
    echo "<p>Error: " . $e->getMessage() . "</p>\n";
}
?>