<?php
require_once 'config/database.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Crear organizaciones demo
    $orgs = [
        ['name' => 'Colegio San José', 'domain' => 'sanjose.edu', 'plan' => 'premium', 'max_users' => 500],
        ['name' => 'Instituto Tecnológico', 'domain' => 'itec.edu', 'plan' => 'basic', 'max_users' => 50],
        ['name' => 'Universidad Nacional', 'domain' => 'unacional.edu', 'plan' => 'premium', 'max_users' => 1000]
    ];
    
    foreach ($orgs as $org) {
        $stmt = $pdo->prepare("INSERT INTO organizations (name, domain, plan, max_users, status) VALUES (?, ?, ?, ?, 'active')");
        $stmt->execute([$org['name'], $org['domain'], $org['plan'], $org['max_users']]);
        $orgId = $pdo->lastInsertId();
        
        // Crear usuarios para cada organización
        $users = [
            ['name' => 'Director General', 'email' => "director@{$org['domain']}", 'role' => 'admin'],
            ['name' => 'Prof. María García', 'email' => "maria@{$org['domain']}", 'role' => 'teacher'],
            ['name' => 'Prof. Juan Pérez', 'email' => "juan@{$org['domain']}", 'role' => 'teacher'],
            ['name' => 'Ana Rodríguez', 'email' => "ana@{$org['domain']}", 'role' => 'student'],
            ['name' => 'Carlos López', 'email' => "carlos@{$org['domain']}", 'role' => 'student'],
            ['name' => 'Sofia Martínez', 'email' => "sofia@{$org['domain']}", 'role' => 'student']
        ];
        
        foreach ($users as $user) {
            $stmt = $pdo->prepare("INSERT INTO users (organization_id, name, email, password, role) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$orgId, $user['name'], $user['email'], password_hash('123456', PASSWORD_DEFAULT), $user['role']]);
            
            if ($user['role'] === 'teacher') {
                $teacherId = $pdo->lastInsertId();
                
                // Crear clases para profesores
                $classes = [
                    ['name' => 'Matemáticas 10°A', 'subject' => 'Matemáticas', 'grade' => '10°A'],
                    ['name' => 'Física 11°B', 'subject' => 'Física', 'grade' => '11°B']
                ];
                
                foreach ($classes as $class) {
                    $stmt = $pdo->prepare("INSERT INTO classes (organization_id, teacher_id, name, subject, grade) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$orgId, $teacherId, $class['name'], $class['subject'], $class['grade']]);
                    $classId = $pdo->lastInsertId();
                    
                    // Crear tareas para cada clase
                    $assignments = [
                        ['title' => 'Ejercicios de Álgebra', 'description' => 'Resolver los ejercicios del capítulo 5', 'due_date' => date('Y-m-d', strtotime('+7 days'))],
                        ['title' => 'Proyecto Final', 'description' => 'Presentación sobre aplicaciones prácticas', 'due_date' => date('Y-m-d', strtotime('+14 days'))]
                    ];
                    
                    foreach ($assignments as $assignment) {
                        $stmt = $pdo->prepare("INSERT INTO assignments (class_id, title, description, due_date, status) VALUES (?, ?, ?, ?, 'active')");
                        $stmt->execute([$classId, $assignment['title'], $assignment['description'], $assignment['due_date']]);
                    }
                }
            }
        }
    }
    
    echo "✅ Datos demo creados exitosamente!";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>