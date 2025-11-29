<?php
require_once 'config.php';

echo "<h1>StudyMate Academic System Migration</h1>\n";

try {
    $pdo = getDBConnection();
    
    echo "<h2>Ejecutando migración...</h2>\n";
    
    // Crear tablas directamente
    $tables = [
        "CREATE TABLE IF NOT EXISTS subjects (
            id INT AUTO_INCREMENT PRIMARY KEY,
            organization_id INT NOT NULL,
            name VARCHAR(100) NOT NULL,
            code VARCHAR(20),
            description TEXT,
            area VARCHAR(50),
            hours_per_week INT DEFAULT 2,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
            INDEX idx_org_status (organization_id, status)
        )",
        
        "CREATE TABLE IF NOT EXISTS academic_grades (
            id INT AUTO_INCREMENT PRIMARY KEY,
            organization_id INT NOT NULL,
            name VARCHAR(50) NOT NULL,
            level VARCHAR(20),
            description TEXT,
            sort_order INT DEFAULT 0,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
            INDEX idx_org_status (organization_id, status),
            INDEX idx_sort_order (sort_order)
        )",
        
        "CREATE TABLE IF NOT EXISTS achievements (
            id INT AUTO_INCREMENT PRIMARY KEY,
            organization_id INT NOT NULL,
            subject_id INT NOT NULL,
            grade_id INT NOT NULL,
            period ENUM('1', '2', '3', '4') NOT NULL,
            code VARCHAR(20),
            description TEXT NOT NULL,
            competency_type ENUM('cognitive', 'procedural', 'attitudinal') DEFAULT 'cognitive',
            weight DECIMAL(3,2) DEFAULT 1.00,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
            FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
            FOREIGN KEY (grade_id) REFERENCES academic_grades(id) ON DELETE CASCADE,
            INDEX idx_org_subject_grade (organization_id, subject_id, grade_id),
            INDEX idx_period (period)
        )",
        
        "CREATE TABLE IF NOT EXISTS student_grades (
            id INT AUTO_INCREMENT PRIMARY KEY,
            organization_id INT NOT NULL,
            student_id INT NOT NULL,
            subject_id INT NOT NULL,
            achievement_id INT NOT NULL,
            grade_id INT NOT NULL,
            period ENUM('1', '2', '3', '4') NOT NULL,
            score DECIMAL(4,2) NOT NULL,
            qualitative_grade ENUM('Superior', 'Alto', 'Básico', 'Bajo') NOT NULL,
            observations TEXT,
            graded_by INT NOT NULL,
            graded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
            FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
            FOREIGN KEY (achievement_id) REFERENCES achievements(id) ON DELETE CASCADE,
            FOREIGN KEY (grade_id) REFERENCES academic_grades(id) ON DELETE CASCADE,
            FOREIGN KEY (graded_by) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_student_period (student_id, period),
            INDEX idx_org_grade_period (organization_id, grade_id, period),
            UNIQUE KEY unique_student_achievement (student_id, achievement_id, period)
        )",
        
        "CREATE TABLE IF NOT EXISTS academic_periods (
            id INT AUTO_INCREMENT PRIMARY KEY,
            organization_id INT NOT NULL,
            name VARCHAR(50) NOT NULL,
            period_number ENUM('1', '2', '3', '4') NOT NULL,
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            is_active BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
            INDEX idx_org_active (organization_id, is_active)
        )"
    ];
    
    foreach ($tables as $sql) {
        try {
            $pdo->exec($sql);
            echo "<p>✅ Tabla creada correctamente</p>\n";
        } catch (PDOException $e) {
            echo "<p>⚠️ Error creando tabla: " . $e->getMessage() . "</p>\n";
        }
    }
    
    echo "<h2>Poblando datos de ejemplo...</h2>\n";
    
    // Verificar si ya existen datos
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM subjects WHERE organization_id = 1");
    $stmt->execute();
    $subjectCount = $stmt->fetchColumn();
    
    if ($subjectCount == 0) {
        // Insertar datos de ejemplo
        $sampleData = [
            "INSERT INTO subjects (organization_id, name, code, area, hours_per_week) VALUES 
                (1, 'Matemáticas', 'MAT', 'Ciencias Exactas', 4),
                (1, 'Español', 'ESP', 'Humanidades', 4),
                (1, 'Ciencias Naturales', 'CN', 'Ciencias', 3),
                (1, 'Ciencias Sociales', 'CS', 'Humanidades', 3),
                (1, 'Inglés', 'ING', 'Idiomas', 2),
                (1, 'Educación Física', 'EF', 'Deportes', 2)",
            
            "INSERT INTO academic_grades (organization_id, name, level, sort_order) VALUES
                (1, '6°', 'secundaria', 6),
                (1, '7°', 'secundaria', 7),
                (1, '8°', 'secundaria', 8),
                (1, '9°', 'secundaria', 9),
                (1, '10°', 'media', 10),
                (1, '11°', 'media', 11)",
            
            "INSERT INTO academic_periods (organization_id, name, period_number, start_date, end_date, is_active) VALUES
                (1, 'Primer Período 2024', '1', '2024-02-01', '2024-04-30', TRUE)",
            
            "INSERT INTO users (organization_id, username, email, password_hash, full_name, role, grade_level, status) VALUES 
                (1, 'ana.garcia', 'ana.garcia@demo.com', '$2y$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Ana García Pérez', 'student', '6°', 'active'),
                (1, 'carlos.lopez', 'carlos.lopez@demo.com', '$2y$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Carlos López Martín', 'student', '6°', 'active'),
                (1, 'maria.rodriguez', 'maria.rodriguez@demo.com', '$2y$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'María Rodríguez Silva', 'student', '7°', 'active')",
            
            "INSERT INTO users (organization_id, username, email, password_hash, full_name, role, status) VALUES 
                (1, 'prof.matematicas', 'matematicas@demo.com', '$2y$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Prof. Elena Matemáticas', 'teacher', 'active'),
                (1, 'prof.espanol', 'espanol@demo.com', '$2y$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Prof. Roberto Español', 'teacher', 'active')"
        ];
        
        foreach ($sampleData as $sql) {
            try {
                $pdo->exec($sql);
                echo "<p>✅ Datos insertados correctamente</p>\n";
            } catch (PDOException $e) {
                echo "<p>⚠️ Error insertando datos: " . $e->getMessage() . "</p>\n";
            }
        }
        
        // Insertar logros de ejemplo
        echo "<h3>Insertando logros de ejemplo...</h3>\n";
        
        $achievementsSQL = "INSERT INTO achievements (organization_id, subject_id, grade_id, period, code, description, competency_type, weight) VALUES 
            (1, 1, 1, '1', 'MAT-6-1-01', 'Resuelve operaciones básicas con números naturales', 'cognitive', 1.0),
            (1, 1, 1, '1', 'MAT-6-1-02', 'Identifica y clasifica figuras geométricas básicas', 'cognitive', 1.0),
            (1, 2, 1, '1', 'ESP-6-1-01', 'Lee y comprende textos narrativos simples', 'cognitive', 1.0),
            (1, 2, 1, '1', 'ESP-6-1-02', 'Escribe párrafos coherentes con buena ortografía', 'procedural', 1.0)";
        
        try {
            $pdo->exec($achievementsSQL);
            echo "<p>✅ Logros de ejemplo insertados</p>\n";
        } catch (PDOException $e) {
            echo "<p>⚠️ Error insertando logros: " . $e->getMessage() . "</p>\n";
        }
        
        // Insertar calificaciones de ejemplo
        echo "<h3>Insertando calificaciones de ejemplo...</h3>\n";
        
        $gradesSQL = "INSERT INTO student_grades (organization_id, student_id, subject_id, achievement_id, grade_id, period, score, qualitative_grade, graded_by) VALUES 
            (1, 1, 1, 1, 1, '1', 8.5, 'Alto', 4),
            (1, 1, 1, 2, 1, '1', 9.2, 'Superior', 4),
            (1, 2, 1, 1, 1, '1', 7.8, 'Alto', 4),
            (1, 2, 1, 2, 1, '1', 8.0, 'Alto', 4)";
        
        try {
            $pdo->exec($gradesSQL);
            echo "<p>✅ Calificaciones de ejemplo insertadas</p>\n";
        } catch (PDOException $e) {
            echo "<p>⚠️ Error insertando calificaciones: " . $e->getMessage() . "</p>\n";
        }
        
    } else {
        echo "<p>ℹ️ Los datos ya existen, omitiendo inserción</p>\n";
    }
    
    echo "<h2>✅ Migración completada exitosamente</h2>\n";
    echo "<p><strong>Usuarios de prueba creados:</strong></p>\n";
    echo "<ul>\n";
    echo "<li>Estudiantes: ana.garcia@demo.com, carlos.lopez@demo.com, maria.rodriguez@demo.com (contraseña: 123456)</li>\n";
    echo "<li>Docentes: matematicas@demo.com, espanol@demo.com (contraseña: 123456)</li>\n";
    echo "</ul>\n";
    
    echo "<p><strong>Accesos:</strong></p>\n";
    echo "<ul>\n";
    echo "<li><a href='academic_admin.html'>Panel de Administración Académica</a></li>\n";
    echo "<li><a href='teacher_grades.html'>Panel de Calificaciones para Docentes</a></li>\n";
    echo "</ul>\n";
    
} catch (Exception $e) {
    echo "<h2>❌ Error en la migración</h2>\n";
    echo "<p>Error: " . $e->getMessage() . "</p>\n";
}
?>