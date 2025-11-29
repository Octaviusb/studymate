<?php
require_once 'config.php';

try {
    $pdo = getDBConnection();
    
    echo "<h1>Expandiendo Sistema de Roles y Permisos</h1>";
    
    // Actualizar enum de roles
    $pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('student', 'parent', 'teacher', 'secretary', 'coordinator', 'admin', 'super_admin') DEFAULT 'student'");
    echo "<p>✅ Roles expandidos</p>";
    
    // Crear tabla de permisos
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS permissions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            role VARCHAR(20) NOT NULL,
            permission VARCHAR(50) NOT NULL,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_role_permission (role, permission)
        )
    ");
    echo "<p>✅ Tabla de permisos creada</p>";
    
    // Insertar permisos por rol
    $permissions = [
        // Super Admin - Todos los permisos
        ['super_admin', 'manage_organizations', 'Gestionar organizaciones'],
        ['super_admin', 'manage_all_users', 'Gestionar todos los usuarios'],
        ['super_admin', 'system_admin', 'Administración del sistema'],
        
        // Admin - Administrador de organización
        ['admin', 'manage_users', 'Gestionar usuarios de la organización'],
        ['admin', 'manage_subjects', 'Gestionar asignaturas'],
        ['admin', 'manage_grades', 'Gestionar grados académicos'],
        ['admin', 'view_all_reports', 'Ver todos los informes'],
        ['admin', 'modify_grades', 'Modificar calificaciones'],
        
        // Coordinator - Coordinador académico
        ['coordinator', 'manage_teachers', 'Gestionar docentes'],
        ['coordinator', 'manage_subjects', 'Gestionar asignaturas'],
        ['coordinator', 'view_reports', 'Ver informes académicos'],
        ['coordinator', 'modify_grades', 'Modificar calificaciones'],
        ['coordinator', 'manage_achievements', 'Gestionar logros'],
        
        // Secretary - Secretaria
        ['secretary', 'manage_students', 'Gestionar estudiantes'],
        ['secretary', 'view_reports', 'Ver informes'],
        ['secretary', 'send_notifications', 'Enviar notificaciones'],
        
        // Teacher - Docente
        ['teacher', 'create_grades', 'Crear calificaciones'],
        ['teacher', 'view_own_classes', 'Ver sus clases'],
        ['teacher', 'manage_tasks', 'Gestionar tareas'],
        ['teacher', 'view_student_reports', 'Ver informes de estudiantes'],
        
        // Student - Estudiante
        ['student', 'view_own_grades', 'Ver sus calificaciones'],
        ['student', 'submit_tasks', 'Enviar tareas'],
        ['student', 'send_messages', 'Enviar mensajes'],
        ['student', 'view_own_reports', 'Ver sus informes'],
        
        // Parent - Padre de familia
        ['parent', 'view_child_grades', 'Ver calificaciones del hijo'],
        ['parent', 'send_messages', 'Enviar mensajes'],
        ['parent', 'view_child_reports', 'Ver informes del hijo'],
    ];
    
    foreach ($permissions as $perm) {
        try {
            $stmt = $pdo->prepare("INSERT IGNORE INTO permissions (role, permission, description) VALUES (?, ?, ?)");
            $stmt->execute($perm);
        } catch (Exception $e) {
            // Ignorar duplicados
        }
    }
    echo "<p>✅ Permisos insertados</p>";
    
    // Corregir contraseñas de docentes
    $correctHash = password_hash('123456', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE role = 'teacher'");
    $stmt->execute([$correctHash]);
    echo "<p>✅ Contraseñas de docentes corregidas</p>";
    
    // Crear algunos usuarios adicionales con nuevos roles
    $newUsers = [
        [1, 'coordinador', 'coordinador@demo.com', $correctHash, 'Dr. Carlos Coordinador', 'coordinator', 'active'],
        [1, 'secretaria', 'secretaria@demo.com', $correctHash, 'Sra. Ana Secretaria', 'secretary', 'active'],
        [1, 'padre1', 'padre1@demo.com', $correctHash, 'Sr. Juan Padre', 'parent', 'active'],
    ];
    
    foreach ($newUsers as $user) {
        try {
            $stmt = $pdo->prepare("
                INSERT IGNORE INTO users (organization_id, username, email, password_hash, full_name, role, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute($user);
        } catch (Exception $e) {
            // Ignorar duplicados
        }
    }
    echo "<p>✅ Usuarios adicionales creados</p>";
    
    echo "<h2>✅ Sistema de Roles Expandido</h2>";
    echo "<h3>Nuevos Roles Disponibles:</h3>";
    echo "<ul>";
    echo "<li><strong>Super Admin:</strong> Control total del sistema</li>";
    echo "<li><strong>Admin:</strong> Administrador de organización</li>";
    echo "<li><strong>Coordinator:</strong> Coordinador académico</li>";
    echo "<li><strong>Secretary:</strong> Secretaria administrativa</li>";
    echo "<li><strong>Teacher:</strong> Docente (solo crear notas)</li>";
    echo "<li><strong>Student:</strong> Estudiante (solo consultar)</li>";
    echo "<li><strong>Parent:</strong> Padre de familia (consultar hijo)</li>";
    echo "</ul>";
    
    echo "<h3>Nuevos Usuarios Creados:</h3>";
    echo "<ul>";
    echo "<li>coordinador@demo.com - Dr. Carlos Coordinador</li>";
    echo "<li>secretaria@demo.com - Sra. Ana Secretaria</li>";
    echo "<li>padre1@demo.com - Sr. Juan Padre</li>";
    echo "</ul>";
    echo "<p><strong>Contraseña para todos:</strong> 123456</p>";
    
} catch (Exception $e) {
    echo "<h2>❌ Error</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>