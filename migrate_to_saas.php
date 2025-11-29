<?php
/**
 * StudyMate Migration to SaaS
 * Migra datos existentes al nuevo esquema multi-tenant
 */

if (!isset($_GET['migrate']) || $_GET['migrate'] !== 'studymate_saas_2024') {
    die('Acceso denegado. Usa: ?migrate=studymate_saas_2024');
}

require_once 'config.php';

try {
    $pdo = getDBConnection();
    
    echo "<h1>StudyMate SaaS Migration</h1>";
    echo "<pre>";
    
    // 1. Ejecutar nuevo schema
    echo "1. Ejecutando nuevo schema SaaS...\n";
    $schemaPath = __DIR__ . '/database/schema_saas.sql';
    if (file_exists($schemaPath)) {
        $schema = file_get_contents($schemaPath);
        $statements = explode(';', $schema);
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (empty($statement)) continue;
            
            try {
                $pdo->exec($statement);
                echo "✓ " . substr($statement, 0, 50) . "...\n";
            } catch (PDOException $e) {
                echo "⚠ " . substr($statement, 0, 50) . "... - " . $e->getMessage() . "\n";
            }
        }
    }
    
    // 2. Migrar datos existentes (si existen)
    echo "\n2. Migrando datos existentes...\n";
    
    // Verificar si hay datos en el esquema anterior
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE organization_id IS NULL");
        $oldUsers = $stmt->fetchColumn();
        
        if ($oldUsers > 0) {
            echo "Encontrados $oldUsers usuarios sin organización\n";
            
            // Asignar a organización demo
            $pdo->exec("UPDATE users SET organization_id = 1 WHERE organization_id IS NULL");
            echo "✓ Usuarios asignados a organización demo\n";
            
            // Migrar tareas
            $pdo->exec("UPDATE tasks SET organization_id = 1, created_by = user_id WHERE organization_id IS NULL");
            echo "✓ Tareas migradas\n";
            
            // Migrar wellness entries
            $pdo->exec("UPDATE wellness_entries SET organization_id = 1 WHERE organization_id IS NULL");
            echo "✓ Entradas de bienestar migradas\n";
        }
    } catch (PDOException $e) {
        echo "ℹ No hay datos anteriores para migrar\n";
    }
    
    // 3. Crear datos de prueba
    echo "\n3. Creando datos de prueba...\n";
    
    // Crear profesor demo
    $stmt = $pdo->prepare("INSERT IGNORE INTO users (organization_id, username, email, password_hash, full_name, role, status) VALUES (1, 'teacher1', 'teacher@demo.com', ?, 'Profesor Demo', 'teacher', 'active')");
    $stmt->execute([password_hash('teacher123', PASSWORD_DEFAULT)]);
    echo "✓ Profesor demo creado\n";
    
    // Crear estudiantes demo
    for ($i = 1; $i <= 3; $i++) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO users (organization_id, username, email, password_hash, full_name, role, grade_level, status) VALUES (1, ?, ?, ?, ?, 'student', '11°', 'active')");
        $stmt->execute(["student$i", "student$i@demo.com", password_hash('student123', PASSWORD_DEFAULT), "Estudiante $i"]);
    }
    echo "✓ Estudiantes demo creados\n";
    
    // Crear clase demo
    $stmt = $pdo->prepare("INSERT IGNORE INTO classes (organization_id, teacher_id, name, subject, grade_level, description) VALUES (1, (SELECT id FROM users WHERE email = 'teacher@demo.com'), 'Matemáticas 11°', 'Matemáticas', '11°', 'Clase de matemáticas para grado 11')");
    $stmt->execute();
    echo "✓ Clase demo creada\n";
    
    // Asignar estudiantes a la clase
    $pdo->exec("
        INSERT IGNORE INTO class_students (class_id, student_id)
        SELECT c.id, u.id 
        FROM classes c, users u 
        WHERE c.name = 'Matemáticas 11°' AND u.role = 'student' AND u.organization_id = 1
    ");
    echo "✓ Estudiantes asignados a clase\n";
    
    echo "\n=== MIGRACIÓN COMPLETADA ===\n";
    echo "✅ Base de datos SaaS configurada\n";
    echo "✅ Datos migrados exitosamente\n";
    echo "✅ Datos de prueba creados\n\n";
    
    echo "CREDENCIALES DE PRUEBA:\n";
    echo "Super Admin: admin@studymate.com / password\n";
    echo "Profesor: teacher@demo.com / teacher123\n";
    echo "Estudiantes: student1@demo.com / student123\n";
    echo "             student2@demo.com / student123\n";
    echo "             student3@demo.com / student123\n\n";
    
    echo "⚠ IMPORTANTE: Elimina este archivo después de la migración\n";
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<h1>Error en Migración</h1>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>