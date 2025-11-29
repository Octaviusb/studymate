<?php
require_once 'config.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = getDBConnection();
    // Tablas base
    $pdo->exec("CREATE TABLE IF NOT EXISTS exams (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        subject_id INT NULL,
        title VARCHAR(255) NOT NULL,
        instructions TEXT,
        time_limit INT DEFAULT 30,
        deadline DATETIME NULL,
        status VARCHAR(50) DEFAULT 'draft',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS exam_questions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        exam_id INT NOT NULL,
        qtype VARCHAR(20) NOT NULL,
        prompt TEXT NOT NULL,
        options JSON NULL,
        correct JSON NULL,
        points DECIMAL(5,2) DEFAULT 1.00,
        sort_order INT DEFAULT 1,
        FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS exam_submissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        exam_id INT NOT NULL,
        student_id INT NOT NULL,
        answers JSON,
        score DECIMAL(6,2) DEFAULT 0,
        submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) {
    // No cortar flujo; se manejarán fallbacks más abajo
}

if ($method === 'GET') {
    // Listar exámenes, traer un examen con preguntas o listar envíos
    $subjectId = isset($_GET['subject_id']) ? intval($_GET['subject_id']) : null;
    $examId = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : null;
    $studentId = isset($_GET['student_id']) ? intval($_GET['student_id']) : null;
    $submissions = isset($_GET['submissions']) ? intval($_GET['submissions']) : 0;
    try {
        if (!isset($pdo)) throw new Exception('No DB');
        if ($submissions === 1) {
            $sql = "SELECT s.id, s.exam_id, e.title, s.student_id, u.full_name AS student_name, s.score, s.submitted_at 
                    FROM exam_submissions s 
                    JOIN exams e ON e.id = s.exam_id 
                    LEFT JOIN users u ON u.id = s.student_id 
                    ORDER BY s.submitted_at DESC";
            $stmt = $pdo->query($sql);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['submissions' => $rows]);
        } elseif ($studentId) {
            $stmt = $pdo->prepare("SELECT s.id, s.exam_id, e.title, s.score, s.submitted_at FROM exam_submissions s JOIN exams e ON e.id = s.exam_id WHERE s.student_id = ? ORDER BY s.submitted_at DESC");
            $stmt->execute([$studentId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['submissions' => $rows]);
        } elseif ($examId) {
            $stmt = $pdo->prepare("SELECT id, title, instructions, time_limit, deadline, status FROM exams WHERE id = ?");
            $stmt->execute([$examId]);
            $exam = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$exam) { http_response_code(404); echo json_encode(['message' => 'Examen no encontrado']); exit; }
            $qstmt = $pdo->prepare("SELECT id, qtype, prompt, options, points, sort_order FROM exam_questions WHERE exam_id = ? ORDER BY sort_order ASC, id ASC");
            $qstmt->execute([$examId]);
            $questions = array_map(function($q){
                $q['options'] = $q['options'] ? json_decode($q['options'], true) : null;
                return $q;
            }, $qstmt->fetchAll(PDO::FETCH_ASSOC));
            echo json_encode(['exam' => $exam, 'questions' => $questions]);
        } else {
            $sql = "SELECT id, title, instructions, time_limit, deadline, status FROM exams";
            $params = [];
            if ($subjectId) { $sql .= " WHERE subject_id = ?"; $params[] = $subjectId; }
            $sql .= " ORDER BY created_at DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['exams' => $rows]);
        }
    } catch (Exception $e) {
        if ($submissions === 1) {
            echo json_encode(['submissions' => []]);
        } elseif ($studentId) {
            echo json_encode(['submissions' => []]);
        } elseif ($examId) {
            http_response_code(404);
            echo json_encode(['message' => 'Examen no disponible']);
        } else {
            echo json_encode(['exams' => []]);
        }
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
        // Registrar envío y autocalificar (múltiple / única)
        $examId = intval($input['exam_id'] ?? 0);
        $studentId = intval($input['student_id'] ?? 0);
        $answers = $input['answers'] ?? [];
        try {
            if (!isset($pdo)) throw new Exception('No DB');
            $qstmt = $pdo->prepare("SELECT id, qtype, options, correct, points FROM exam_questions WHERE exam_id = ? ORDER BY sort_order ASC, id ASC");
            $qstmt->execute([$examId]);
            $qs = $qstmt->fetchAll(PDO::FETCH_ASSOC);
            $total = 0.0; $max = 0.0; $graded = [];
            foreach ($qs as $idx => $q) {
                $qid = $q['id'];
                $qtype = $q['qtype'];
                $points = floatval($q['points']);
                $max += $points;
                $ans = $answers[strval($qid)] ?? $answers[$idx] ?? null;
                $isCorrect = false;
                if ($qtype === 'multiple') {
                    $correct = json_decode($q['correct'] ?: '[]', true) ?: [];
                    $ansIdx = is_array($ans) ? $ans : [$ans];
                    sort($correct); sort($ansIdx);
                    $isCorrect = ($ansIdx == $correct);
                } elseif ($qtype === 'single') {
                    $correct = json_decode($q['correct'] ?: '[]', true) ?: [];
                    $isCorrect = isset($correct[0]) && strval($ans) !== '' && strtolower(trim($ans)) === strtolower(trim($correct[0]));
                } else {
                    // abierta: sin auto-calificación
                    $isCorrect = false;
                }
                $score = $isCorrect ? $points : 0.0;
                $total += $score;
                $graded[strval($qid)] = ['points' => $points, 'score' => $score, 'auto' => in_array($qtype, ['multiple','single'])];
            }

            $sstmt = $pdo->prepare("INSERT INTO exam_submissions (exam_id, student_id, answers, score) VALUES (?, ?, ?, ?)");
            $sstmt->execute([$examId, $studentId, json_encode($answers, JSON_UNESCAPED_UNICODE), $total]);
            echo json_encode(['message' => 'Envío registrado', 'score' => $total, 'max' => $max, 'details' => $graded]);
        } catch (Exception $e) {
            // Fallback demo
            echo json_encode(['message' => 'Envío registrado (demo)', 'score' => 0, 'max' => 0, 'details' => []]);
        }
        exit;
    }

    // Crear examen (por defecto)
    $userId = intval($input['user_id'] ?? 0);
    $title = trim($input['title'] ?? 'Examen');
    $subjectId = isset($input['subject_id']) ? intval($input['subject_id']) : null;
    $instructions = trim($input['instructions'] ?? '');
    $deadline = trim($input['deadline'] ?? '');
    $timeLimit = isset($input['time_limit']) ? intval($input['time_limit']) : 30;
    $questions = $input['questions'] ?? [];

    try {
        if (!isset($pdo)) throw new Exception('No DB');
        $stmt = $pdo->prepare("INSERT INTO exams (user_id, subject_id, title, instructions, time_limit, deadline, status) VALUES (?, ?, ?, ?, ?, ?, 'draft')");
        $stmt->execute([$userId, $subjectId, $title, $instructions, $timeLimit, $deadline ?: null]);
        $examId = $pdo->lastInsertId();

        if (is_array($questions)) {
            $qstmt = $pdo->prepare("INSERT INTO exam_questions (exam_id, qtype, prompt, options, correct, points, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $order = 1;
            foreach ($questions as $q) {
                $qtype = $q['type'] ?? 'multiple';
                $prompt = $q['prompt'] ?? '';
                $opts = isset($q['options']) ? json_encode($q['options'], JSON_UNESCAPED_UNICODE) : null;
                $correct = isset($q['correct']) ? json_encode($q['correct'], JSON_UNESCAPED_UNICODE) : null;
                $points = isset($q['points']) ? floatval($q['points']) : 1.0;
                $qstmt->execute([$examId, $qtype, $prompt, $opts, $correct, $points, $order++]);
            }
        }

        echo json_encode([
            'message' => 'Examen creado',
            'exam_id' => $examId,
            'questions_saved' => is_array($questions) ? count($questions) : 0,
            'status' => 'draft'
        ]);
    } catch (Exception $e) {
        // Fallback demo sin DB
        echo json_encode([
            'message' => 'Examen registrado (demo)',
            'exam_id' => rand(1000,9999),
            'questions_saved' => is_array($questions) ? count($questions) : 0,
            'status' => 'draft'
        ]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['message' => 'Método no permitido']);
