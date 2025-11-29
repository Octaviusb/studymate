<?php
/**
 * Execute Complete SaaS Migration
 * Ejecuta la migración completa automáticamente
 */

require_once 'config.php';

try {
    $pdo = getDBConnection();
    
    echo "<h1>StudyMate Complete SaaS Migration</h1>";
    echo "<pre>";
    
    // 1. Ejecutar schema SaaS
    echo "=== EJECUTANDO SCHEMA SAAS ===\n";
    $schemaPath = __DIR__ . '/database/schema_saas.sql';
    if (file_exists($schemaPath)) {
        $schema = file_get_contents($schemaPath);
        $statements = explode(';', $schema);
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (empty($statement)) continue;
            
            try {
                $pdo->exec($statement);
                echo "✓ " . substr($statement, 0, 60) . "...\n";
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'already exists') === false) {
                    echo "⚠ " . substr($statement, 0, 60) . "... - " . $e->getMessage() . "\n";
                }
            }
        }
    }
    
    // 2. Crear datos completos de prueba
    echo "\n=== CREANDO DATOS DE PRUEBA COMPLETOS ===\n";
    
    // Super Admin
    $stmt = $pdo->prepare("INSERT IGNORE INTO users (organization_id, username, email, password_hash, full_name, role, status) VALUES (1, 'superadmin', 'admin@studymate.com', ?, 'Super Admin', 'super_admin', 'active')");
    $stmt->execute([password_hash('admin123', PASSWORD_DEFAULT)]);
    echo "✓ Super Admin creado\n";
    
    // Admin de organización
    $stmt = $pdo->prepare("INSERT IGNORE INTO users (organization_id, username, email, password_hash, full_name, role, status) VALUES (1, 'orgadmin', 'orgadmin@demo.com', ?, 'Admin Organización', 'admin', 'active')");
    $stmt->execute([password_hash('admin123', PASSWORD_DEFAULT)]);
    echo "✓ Admin de organización creado\n";
    
    // Profesores
    $teachers = [
        ['teacher1', 'teacher1@demo.com', 'Profesor Matemáticas'],
        ['teacher2', 'teacher2@demo.com', 'Profesora Español'],
        ['teacher3', 'teacher3@demo.com', 'Profesor Ciencias']
    ];
    
    foreach ($teachers as $teacher) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO users (organization_id, username, email, password_hash, full_name, role, status) VALUES (1, ?, ?, ?, ?, 'teacher', 'active')");
        $stmt->execute([$teacher[0], $teacher[1], password_hash('teacher123', PASSWORD_DEFAULT), $teacher[2]]);
    }
    echo "✓ 3 Profesores creados\n";
    
    // Estudiantes
    for ($i = 1; $i <= 10; $i++) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO users (organization_id, username, email, password_hash, full_name, role, grade_level, status) VALUES (1, ?, ?, ?, ?, 'student', '11°', 'active')");
        $stmt->execute(["student$i", "student$i@demo.com", password_hash('student123', PASSWORD_DEFAULT), "Estudiante $i"]);
    }
    echo "✓ 10 Estudiantes creados\n";
    
    // Clases
    $classes = [
        ['Matemáticas 11°', 'Matemáticas', 'teacher1@demo.com'],
        ['Español 11°', 'Español', 'teacher2@demo.com'],
        ['Ciencias 11°', 'Ciencias', 'teacher3@demo.com']
    ];
    
    foreach ($classes as $class) {
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO classes (organization_id, teacher_id, name, subject, grade_level, description) 
            VALUES (1, (SELECT id FROM users WHERE email = ?), ?, ?, '11°', ?)
        ");
        $stmt->execute([$class[2], $class[0], $class[1], "Clase de {$class[1]} para grado 11"]);
    }
    echo "✓ 3 Clases creadas\n";
    
    // Asignar estudiantes a clases
    $pdo->exec("
        INSERT IGNORE INTO class_students (class_id, student_id)
        SELECT c.id, u.id 
        FROM classes c 
        CROSS JOIN users u 
        WHERE c.organization_id = 1 AND u.role = 'student' AND u.organization_id = 1
    ");
    echo "✓ Estudiantes asignados a todas las clases\n";
    
    // Crear tareas de ejemplo
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO tasks (organization_id, created_by, class_id, title, subject, description, due_date, priority, task_type)
        SELECT 1, u.id, c.id, 'Tarea de Ejemplo', c.subject, 'Esta es una tarea de ejemplo', DATE_ADD(CURDATE(), INTERVAL 7 DAY), 'medium', 'assignment'
        FROM users u
        JOIN classes c ON c.teacher_id = u.id
        WHERE u.role = 'teacher' AND u.organization_id = 1
    ");
    $stmt->execute();
    echo "✓ Tareas de ejemplo creadas\n";
    
    // Asignar tareas a estudiantes
    $pdo->exec("
        INSERT IGNORE INTO student_tasks (task_id, student_id)
        SELECT t.id, cs.student_id
        FROM tasks t
        JOIN class_students cs ON cs.class_id = t.class_id
        WHERE t.task_type = 'assignment'
    ");
    echo "✓ Tareas asignadas a estudiantes\n";
    
    // Crear entradas de bienestar de ejemplo
    $moods = ['excellent', 'good', 'okay', 'bad'];
    for ($i = 1; $i <= 5; $i++) {
        $mood = $moods[array_rand($moods)];
        $moodValue = ['excellent' => 5, 'good' => 4, 'okay' => 3, 'bad' => 2][$mood];
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO wellness_entries (organization_id, user_id, mood, mood_value, notes, date)
            VALUES (1, ?, ?, ?, 'Entrada de ejemplo', DATE_SUB(CURDATE(), INTERVAL ? DAY))
        ");
        $stmt->execute([$i, $mood, $moodValue, $i]);
    }
    echo "✓ Entradas de bienestar de ejemplo creadas\n";
    
    // Estadísticas finales
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE organization_id = 1");
    $totalUsers = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM classes WHERE organization_id = 1");
    $totalClasses = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM tasks WHERE organization_id = 1");
    $totalTasks = $stmt->fetchColumn();
    
    echo "\n=== MIGRACIÓN COMPLETADA EXITOSAMENTE ===\n";
    echo "✅ Base de datos SaaS configurada\n";
    echo "✅ $totalUsers usuarios creados\n";
    echo "✅ $totalClasses clases creadas\n";
    echo "✅ $totalTasks tareas creadas\n";
    echo "✅ Sistema multi-tenant activo\n\n";
    
    echo "=== CREDENCIALES DE ACCESO ===\n";
    echo "Super Admin: admin@studymate.com / admin123\n";
    echo "Org Admin: orgadmin@demo.com / admin123\n";
    echo "Profesor 1: teacher1@demo.com / teacher123\n";
    echo "Profesor 2: teacher2@demo.com / teacher123\n";
    echo "Profesor 3: teacher3@demo.com / teacher123\n";
    echo "Estudiantes: student1@demo.com a student10@demo.com / student123\n\n";
    
    echo "=== ACCESOS DIRECTOS ===\n";
    echo "Dashboard SaaS: /dashboard_saas.html\n";
    echo "Test APIs: /test_api.php?test=studymate\n";
    echo "Frontend: /index.html\n\n";
    
    echo "🚀 StudyMate SaaS está listo para usar!\n";
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<h1>Error en Migración</h1>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Verifica la configuración de la base de datos.</p>";
}
?>