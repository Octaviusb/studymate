<?php
require_once 'config.php';

try {
    $pdo = getDBConnection();

    echo "<h1>Creando tablas faltantes en StudyMate SaaS</h1>";
    echo "<pre>";

    // Crear tabla classes
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS classes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            organization_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            subject VARCHAR(255) NOT NULL,
            grade_level VARCHAR(50) NOT NULL,
            description TEXT,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "✅ Tabla 'classes' creada\n";

    // Crear tabla class_students
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS class_students (
            id INT AUTO_INCREMENT PRIMARY KEY,
            class_id INT NOT NULL,
            student_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_class_student (class_id, student_id)
        )
    ");
    echo "✅ Tabla 'class_students' creada\n";

    // Crear tabla tasks
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tasks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            organization_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            due_date DATETIME,
            status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "✅ Tabla 'tasks' creada\n";

    // Crear tabla student_tasks
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS student_tasks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            task_id INT NOT NULL,
            student_id INT NOT NULL,
            completed TINYINT(1) DEFAULT 0,
            completed_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "✅ Tabla 'student_tasks' creada\n";

    // Crear tabla wellness_entries
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS wellness_entries (
            id INT AUTO_INCREMENT PRIMARY KEY,
            organization_id INT NOT NULL,
            user_id INT NOT NULL,
            mood ENUM('excellent', 'good', 'okay', 'bad', 'terrible') NOT NULL,
            mood_value INT NOT NULL,
            notes TEXT,
            date DATE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "✅ Tabla 'wellness_entries' creada\n";

    // Crear tabla chat_sessions
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS chat_sessions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            organization_id INT NOT NULL,
            user_id INT NOT NULL,
            session_id VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "✅ Tabla 'chat_sessions' creada\n";

    // Crear tabla chat_messages
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS chat_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            organization_id INT NOT NULL,
            session_id VARCHAR(255) NOT NULL,
            user_id INT NOT NULL,
            message TEXT NOT NULL,
            response TEXT,
            is_emergency TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "✅ Tabla 'chat_messages' creada\n";

    echo "\n🎉 Todas las tablas faltantes han sido creadas exitosamente!\n";
    echo "Ahora puedes ejecutar populate_data.php nuevamente.\n";

} catch (Exception $e) {
    echo "<h1>Error creando tablas</h1>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>