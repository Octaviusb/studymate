<?php
require_once 'config.php';
header('Content-Type: application/json');

$result = [
    'success' => false,
    'checks' => [],
];

try {
    $pdo = getDBConnection();
    $result['checks']['db_connection'] = 'ok';

    // Versión MySQL
    $version = $pdo->query('SELECT VERSION() as v')->fetch(PDO::FETCH_ASSOC)['v'] ?? null;
    $result['checks']['mysql_version'] = $version;

    // Tablas clave a verificar
    $tables = [
        'users',
        'organizations',
        'subjects',
        'academic_grades',
        'tasks',
        'task_submissions',
        'student_grades',
        'psychological_support_sessions'
    ];

    $result['checks']['tables'] = [];
    foreach ($tables as $t) {
        // Escape LIKE wildcards
        $like = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $t);
        $sql = "SHOW TABLES LIKE '" . $like . "'";
        $exists = $pdo->query($sql)->rowCount() > 0;
        $result['checks']['tables'][$t] = $exists ? 'present' : 'missing';
    }

    // Columnas críticas en users
    $criticalUserCols = ['password_hash','status','role','grade_level','organization_id','full_name','email'];
    if (($result['checks']['tables']['users'] ?? 'missing') === 'present') {
        $cols = [];
        $stmt = $pdo->query("SHOW COLUMNS FROM users");
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $c) { $cols[] = $c['Field']; }
        $missingCols = array_values(array_diff($criticalUserCols, $cols));
        $result['checks']['users_missing_columns'] = $missingCols;
    } else {
        $result['checks']['users_missing_columns'] = 'users table missing';
    }

    $result['success'] = true;
    echo json_encode($result);
} catch (Exception $e) {
    http_response_code(500);
    $result['error'] = $e->getMessage();
    echo json_encode($result);
}
