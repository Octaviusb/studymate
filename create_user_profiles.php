<?php
require_once 'config.php';

try {
    $pdo = getDBConnection();
    
    echo "<h1>Creando Sistema de Perfiles de Usuario</h1>";
    
    // Crear tabla de perfiles de usuario
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_profiles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            organization_id INT NOT NULL,
            
            -- Información Personal
            document_type ENUM('CC', 'TI', 'CE', 'PP') DEFAULT 'CC',
            document_number VARCHAR(20),
            birth_date DATE,
            gender ENUM('M', 'F', 'O') DEFAULT 'M',
            photo_url VARCHAR(255),
            
            -- Información de Contacto
            phone VARCHAR(20),
            mobile VARCHAR(20),
            address TEXT,
            city VARCHAR(100),
            state VARCHAR(100),
            postal_code VARCHAR(10),
            
            -- Información Académica
            current_grade_id INT,
            enrollment_date DATE,
            student_code VARCHAR(20),
            
            -- Información del Acudiente (para estudiantes)
            guardian_name VARCHAR(100),
            guardian_document VARCHAR(20),
            guardian_phone VARCHAR(20),
            guardian_email VARCHAR(100),
            guardian_relationship ENUM('padre', 'madre', 'abuelo', 'abuela', 'tio', 'tia', 'hermano', 'hermana', 'otro') DEFAULT 'padre',
            guardian_address TEXT,
            
            -- Información de Emergencia
            emergency_contact_name VARCHAR(100),
            emergency_contact_phone VARCHAR(20),
            emergency_contact_relationship VARCHAR(50),
            
            -- Información Médica Básica
            blood_type ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'),
            allergies TEXT,
            medical_conditions TEXT,
            
            -- Control
            profile_completed BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
            FOREIGN KEY (current_grade_id) REFERENCES academic_grades(id) ON DELETE SET NULL,
            UNIQUE KEY unique_user_profile (user_id)
        )
    ");
    echo "<p>✅ Tabla user_profiles creada</p>";
    
    // Agregar campos adicionales a la tabla users
    $additionalFields = [
        "ALTER TABLE users ADD COLUMN profile_completed BOOLEAN DEFAULT FALSE AFTER status",
        "ALTER TABLE users ADD COLUMN last_profile_update TIMESTAMP NULL AFTER profile_completed"
    ];
    
    foreach ($additionalFields as $sql) {
        try {
            $pdo->exec($sql);
            echo "<p>✅ Campo agregado a users</p>";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column name') === false) {
                echo "<p>⚠️ Error: " . $e->getMessage() . "</p>";
            }
        }
    }
    
    // Crear perfiles básicos para usuarios existentes
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO user_profiles (user_id, organization_id, profile_completed) 
        SELECT id, organization_id, FALSE FROM users WHERE id > 1
    ");
    $stmt->execute();
    echo "<p>✅ Perfiles básicos creados para usuarios existentes</p>";
    
    // Crear tabla de relaciones padre-hijo
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS parent_student_relations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            parent_id INT NOT NULL,
            student_id INT NOT NULL,
            relationship_type ENUM('padre', 'madre', 'abuelo', 'abuela', 'tio', 'tia', 'hermano', 'hermana', 'tutor', 'otro') DEFAULT 'padre',
            is_primary BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            FOREIGN KEY (parent_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_parent_student (parent_id, student_id)
        )
    ");
    echo "<p>✅ Tabla parent_student_relations creada</p>";
    
    echo "<h2>✅ Sistema de Perfiles Creado</h2>";
    echo "<h3>Campos del Perfil:</h3>";
    echo "<ul>";
    echo "<li><strong>Información Personal:</strong> Documento, fecha nacimiento, género, foto</li>";
    echo "<li><strong>Contacto:</strong> Teléfonos, dirección, ciudad</li>";
    echo "<li><strong>Académico:</strong> Grado actual, código estudiantil</li>";
    echo "<li><strong>Acudiente:</strong> Nombre, documento, teléfono, email, parentesco</li>";
    echo "<li><strong>Emergencia:</strong> Contacto de emergencia</li>";
    echo "<li><strong>Médico:</strong> Tipo de sangre, alergias, condiciones</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<h2>❌ Error</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>