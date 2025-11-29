<?php
require_once 'config.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Listar recuperaciones por estudiante o listar entregas
    $studentId = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;
    $submissions = isset($_GET['submissions']) ? intval($_GET['submissions']) : 0;
    try {
        $pdo = getDBConnection();
        $pdo->exec("CREATE TABLE IF NOT EXISTS recovery_tasks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            student_id INT NULL,
            achievement_id INT NULL,
            related_grade_id INT NULL,
            title VARCHAR(255) NOT NULL,
            instructions TEXT,
            deadline DATE,
            status VARCHAR(50) DEFAULT 'assigned',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        if ($submissions === 1) {
            $pdo->exec("CREATE TABLE IF NOT EXISTS recovery_submissions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                recovery_id INT NOT NULL,
                student_id INT NOT NULL,
                content TEXT,
                submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            if ($studentId > 0) {
                $stmt = $pdo->prepare("SELECT rs.*, rt.title FROM recovery_submissions rs JOIN recovery_tasks rt ON rt.id = rs.recovery_id WHERE rs.student_id = ? ORDER BY rs.submitted_at DESC");
                $stmt->execute([$studentId]);
            } else {
                $stmt = $pdo->query("SELECT rs.*, rt.title FROM recovery_submissions rs JOIN recovery_tasks rt ON rt.id = rs.recovery_id ORDER BY rs.submitted_at DESC");
            }
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['submissions' => $rows]);
        } else {
            if ($studentId > 0) {
                $stmt = $pdo->prepare("SELECT * FROM recovery_tasks WHERE student_id = ? ORDER BY created_at DESC");
                $stmt->execute([$studentId]);
            } else {
                $stmt = $pdo->query("SELECT * FROM recovery_tasks ORDER BY created_at DESC");
            }
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['recoveries' => $rows]);
        }
    } catch (Exception $e) {
        echo json_encode($submissions === 1 ? ['submissions' => []] : ['recoveries' => []]);
    }
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        http_response_code(400);
        echo json_encode(['message' => 'JSON inválido']);
        exit;
    }
    $action = $input['action'] ?? 'create';

    if ($action === 'submit') {
        $studentId = intval($input['student_id'] ?? 0);
        $recoveryId = intval($input['recovery_id'] ?? 0);
        $content = trim($input['content'] ?? '');
        try {
            $pdo = getDBConnection();
            $pdo->exec("CREATE TABLE IF NOT EXISTS recovery_submissions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                recovery_id INT NOT NULL,
                student_id INT NOT NULL,
                content TEXT,
                submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $stmt = $pdo->prepare("INSERT INTO recovery_submissions (recovery_id, student_id, content) VALUES (?, ?, ?)");
            $stmt->execute([$recoveryId, $studentId, $content]);
            echo json_encode(['message' => 'Entrega registrada', 'submission_id' => $pdo->lastInsertId()]);
        } catch (Exception $e) {
            echo json_encode(['message' => 'Entrega registrada (demo)', 'submission_id' => rand(1000,9999)]);
        }
        exit;
    }

    $userId = isset($input['user_id']) ? intval($input['user_id']) : 0;
    $title = trim($input['title'] ?? 'Recuperación');
    $instructions = trim($input['instructions'] ?? '');
    date_default_timezone_set('America/Bogota');
    $deadline = trim($input['deadline'] ?? date('Y-m-d', time() + 7*24*60*60));
    $studentId = isset($input['student_id']) ? intval($input['student_id']) : null;
    $achievementId = isset($input['achievement_id']) ? intval($input['achievement_id']) : null;
    $gradeId = isset($input['grade_id']) ? intval($input['grade_id']) : null;

    try {
        $pdo = getDBConnection();
        $pdo->exec("CREATE TABLE IF NOT EXISTS recovery_tasks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            student_id INT NULL,
            achievement_id INT NULL,
            related_grade_id INT NULL,
            title VARCHAR(255) NOT NULL,
            instructions TEXT,
            deadline DATE,
            status VARCHAR(50) DEFAULT 'assigned',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $stmt = $pdo->prepare("INSERT INTO recovery_tasks (user_id, student_id, achievement_id, related_grade_id, title, instructions, deadline) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $studentId, $achievementId, $gradeId, $title, $instructions, $deadline]);

        echo json_encode([
            'message' => 'Recuperación creada',
            'id' => $pdo->lastInsertId(),
            'status' => 'assigned'
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'message' => 'Recuperación registrada (demo)',
            'id' => rand(1000,9999),
            'status' => 'assigned'
        ]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['message' => 'Método no permitido']);
