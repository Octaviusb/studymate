<?php
require_once 'config.php';

try {
    $pdo = getDBConnection();
    
    echo "<h1>Expandiendo tabla Organizations</h1>";
    
    // Agregar nuevas columnas
    $columns = [
        "ALTER TABLE organizations ADD COLUMN address VARCHAR(255) AFTER domain",
        "ALTER TABLE organizations ADD COLUMN city VARCHAR(100) AFTER address", 
        "ALTER TABLE organizations ADD COLUMN state VARCHAR(100) AFTER city",
        "ALTER TABLE organizations ADD COLUMN country VARCHAR(100) DEFAULT 'Colombia' AFTER state",
        "ALTER TABLE organizations ADD COLUMN phone VARCHAR(20) AFTER country",
        "ALTER TABLE organizations ADD COLUMN contact_email VARCHAR(100) AFTER phone",
        "ALTER TABLE organizations ADD COLUMN contact_name VARCHAR(100) AFTER contact_email",
        "ALTER TABLE organizations ADD COLUMN contact_position VARCHAR(100) AFTER contact_name",
        "ALTER TABLE organizations ADD COLUMN website VARCHAR(255) AFTER contact_position",
        "ALTER TABLE organizations ADD COLUMN description TEXT AFTER website"
    ];
    
    foreach ($columns as $sql) {
        try {
            $pdo->exec($sql);
            echo "<p>✅ Columna agregada</p>";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                echo "<p>ℹ️ Columna ya existe</p>";
            } else {
                echo "<p>⚠️ Error: " . $e->getMessage() . "</p>";
            }
        }
    }
    
    // Actualizar organización demo con datos completos
    $stmt = $pdo->prepare("
        UPDATE organizations 
        SET address = ?, city = ?, state = ?, phone = ?, contact_email = ?, 
            contact_name = ?, contact_position = ?, website = ?, description = ?
        WHERE id = 1
    ");
    $stmt->execute([
        'Calle 123 #45-67',
        'Bogotá',
        'Cundinamarca', 
        '+57 1 234-5678',
        'contacto@demoschool.edu.co',
        'María González',
        'Rectora',
        'https://demoschool.edu.co',
        'Institución educativa de excelencia académica comprometida con la formación integral de estudiantes.'
    ]);
    
    echo "<h2>✅ Tabla expandida exitosamente</h2>";
    echo "<p>Nuevos campos agregados:</p>";
    echo "<ul>";
    echo "<li>Dirección</li>";
    echo "<li>Ciudad</li>";
    echo "<li>Departamento/Estado</li>";
    echo "<li>País</li>";
    echo "<li>Teléfono</li>";
    echo "<li>Email de contacto</li>";
    echo "<li>Nombre del responsable</li>";
    echo "<li>Cargo del responsable</li>";
    echo "<li>Sitio web</li>";
    echo "<li>Descripción</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<h2>❌ Error</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>