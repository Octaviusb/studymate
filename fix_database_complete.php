<?php
require_once 'config.php';
header('Content-Type: application/json');

$out = ['success' => false, 'steps' => []];

try {
    $pdo = getDBConnection();

    // Organizations
    $pdo->exec("CREATE TABLE IF NOT EXISTS organizations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_name (name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $out['steps'][] = 'organizations ensured';

    // Academic grades
    $pdo->exec("CREATE TABLE IF NOT EXISTS academic_grades (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_name (name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $out['steps'][] = 'academic_grades ensured';

    // Subjects
    $pdo->exec("CREATE TABLE IF NOT EXISTS subjects (
        id INT AUTO_INCREMENT PRIMARY KEY,
        organization_id INT NULL,
        name VARCHAR(255) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_org (organization_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $out['steps'][] = 'subjects ensured';

    // Tasks
    $pdo->exec("CREATE TABLE IF NOT EXISTS tasks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        organization_id INT NULL,
        teacher_id INT NULL,
        subject_id INT NULL,
        grade_id INT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT NULL,
        due_date DATE NULL,
        max_score DECIMAL(5,2) NOT NULL DEFAULT 10.00,
        status VARCHAR(20) NOT NULL DEFAULT 'active',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_org (organization_id),
        INDEX idx_teacher (teacher_id),
        INDEX idx_subject (subject_id),
        INDEX idx_grade (grade_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $out['steps'][] = 'tasks ensured';

    // Task submissions
    $pdo->exec("CREATE TABLE IF NOT EXISTS task_submissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        task_id INT NOT NULL,
        student_id INT NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'pending',
        score DECIMAL(5,2) NULL,
        submitted_at DATETIME NULL,
        INDEX idx_task (task_id),
        INDEX idx_student (student_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $out['steps'][] = 'task_submissions ensured';

    // Student grades (minimal) - ensure table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS student_grades (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        subject_id INT NOT NULL,
        organization_id INT NULL,
        numeric_grade DECIMAL(5,2) NOT NULL DEFAULT 0.00,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_student (student_id),
        INDEX idx_subject (subject_id),
        INDEX idx_org (organization_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $out['steps'][] = 'student_grades ensured';

    // Psychological support sessions (used by psychological_support_api.php)
    $pdo->exec("CREATE TABLE IF NOT EXISTS psychological_support_sessions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        session_id VARCHAR(64) NOT NULL,
        message TEXT NOT NULL,
        response TEXT NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_student (student_id),
        INDEX idx_session (session_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $out['steps'][] = 'psychological_support_sessions ensured';

    // Ensure critical columns on users
    $cols = [];
    foreach ($pdo->query("SHOW COLUMNS FROM users") as $c) { $cols[$c['Field']] = true; }

    $alterSteps = [];
    if (!isset($cols['password_hash'])) $alterSteps[] = "ADD COLUMN password_hash VARCHAR(255) NULL AFTER email";
    if (!isset($cols['status'])) $alterSteps[] = "ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'active'";
    if (!isset($cols['role'])) $alterSteps[] = "ADD COLUMN role VARCHAR(50) NOT NULL DEFAULT 'student'";
    if (!isset($cols['grade_level'])) $alterSteps[] = "ADD COLUMN grade_level VARCHAR(100) NULL";
    if (!isset($cols['organization_id'])) $alterSteps[] = "ADD COLUMN organization_id INT NULL";
    if (!isset($cols['full_name'])) $alterSteps[] = "ADD COLUMN full_name VARCHAR(255) NULL";
    if (!isset($cols['email'])) $alterSteps[] = "ADD COLUMN email VARCHAR(255) NULL";

    if (!empty($alterSteps)) {
        $pdo->exec("ALTER TABLE users " . implode(', ', $alterSteps));
        $out['steps'][] = 'users columns ensured';
    } else {
        $out['steps'][] = 'users columns already ok';
    }

    $out['success'] = true;
    echo json_encode($out);
} catch (Exception $e) {
    http_response_code(500);
    $out['error'] = $e->getMessage();
    echo json_encode($out);
}
