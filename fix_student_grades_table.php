<?php
require_once 'config.php';
header('Content-Type: application/json');

$out = ['success' => false, 'steps' => []];

try {
    $pdo = getDBConnection();

    // Crear tabla student_grades si no existe
    $sql = "CREATE TABLE IF NOT EXISTS student_grades (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        subject_id INT NOT NULL,
        organization_id INT NULL,
        numeric_grade DECIMAL(5,2) NOT NULL DEFAULT 0.00,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_student (student_id),
        INDEX idx_subject (subject_id),
        INDEX idx_org (organization_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $pdo->exec($sql);
    $out['steps'][] = 'student_grades ensured';

    // Asegurar columnas clave (para entornos donde existe pero incompleta)
    $cols = [];
    foreach ($pdo->query("SHOW COLUMNS FROM student_grades") as $c) { $cols[] = $c['Field']; }

    if (!in_array('organization_id', $cols)) {
        $pdo->exec("ALTER TABLE student_grades ADD COLUMN organization_id INT NULL AFTER subject_id");
        $out['steps'][] = 'organization_id added to student_grades';
    }
    if (!in_array('numeric_grade', $cols)) {
        $pdo->exec("ALTER TABLE student_grades ADD COLUMN numeric_grade DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER organization_id");
        $out['steps'][] = 'numeric_grade added to student_grades';
    }

    $out['success'] = true;
    echo json_encode($out);
} catch (Exception $e) {
    http_response_code(500);
    $out['error'] = $e->getMessage();
    echo json_encode($out);
}
