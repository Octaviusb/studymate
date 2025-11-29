<?php
require_once 'config.php';

try {
    $pdo = getDBConnection();

    echo "🔧 Creando tablas de tareas faltantes...<br><br>";

    // Crear tabla tasks si no existe
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tasks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            organization_id INT NOT NULL,
            teacher_id INT NOT NULL,
            subject_id INT NOT NULL,
            grade_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            due_date DATETIME NOT NULL,
            max_score DECIMAL(5,2) DEFAULT 10.00,
            status ENUM('active','inactive','completed') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    echo "✅ Tabla 'tasks' creada/verificada<br>";

    // Crear tabla task_submissions si no existe
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS task_submissions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            task_id INT NOT NULL,
            student_id INT NOT NULL,
            status ENUM('pending','submitted','graded') DEFAULT 'pending',
            submission_text TEXT,
            submitted_at TIMESTAMP NULL,
            score DECIMAL(5,2) NULL,
            feedback TEXT,
            graded_at TIMESTAMP NULL,
            graded_by INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
            FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    echo "✅ Tabla 'task_submissions' creada/verificada<br>";

    // Insertar algunas tareas de ejemplo
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks");
    $stmt->execute();
    $taskCount = $stmt->fetchColumn();

    if ($taskCount == 0) {
        // Obtener IDs necesarios
        $stmt = $pdo->prepare("SELECT id FROM users WHERE role = 'teacher' LIMIT 1");
        $stmt->execute();
        $teacherId = $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT id FROM subjects LIMIT 1");
        $stmt->execute();
        $subjectId = $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT id FROM academic_grades LIMIT 1");
        $stmt->execute();
        $gradeId = $stmt->fetchColumn();

        if ($teacherId && $subjectId && $gradeId) {
            $tasks = [
                [
                    'title' => 'Ejercicios de Álgebra',
                    'description' => 'Resolver los ejercicios 1-20 del capítulo 3',
                    'due_date' => date('Y-m-d H:i:s', strtotime('+7 days'))
                ],
                [
                    'title' => 'Ensayo de Historia',
                    'description' => 'Escribir un ensayo de 500 palabras sobre la Independencia',
                    'due_date' => date('Y-m-d H:i:s', strtotime('+10 days'))
                ],
                [
                    'title' => 'Laboratorio de Química',
                    'description' => 'Realizar experimento de reacciones ácido-base',
                    'due_date' => date('Y-m-d H:i:s', strtotime('+14 days'))
                ]
            ];

            foreach ($tasks as $task) {
                $stmt = $pdo->prepare("
                    INSERT INTO tasks (organization_id, teacher_id, subject_id, grade_id, title, description, due_date, status)
                    VALUES (1, ?, ?, ?, ?, ?, ?, 'active')
                ");
                $stmt->execute([$teacherId, $subjectId, $gradeId, $task['title'], $task['description'], $task['due_date']]);
                echo "✅ Tarea '{$task['title']}' creada<br>";
            }
        }
    } else {
        echo "✅ Ya existen tareas en la base de datos<br>";
    }

    echo "<br>🎉 Sistema de tareas configurado correctamente<br>";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>