<?php
require_once dirname(__DIR__) . '/../config.php';
require_once dirname(__DIR__) . '/../utils/tenant.php';
require_once dirname(__DIR__) . '/../utils/security.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$path = str_replace('/student-grades', '', $_SERVER['REQUEST_URI']);
$path = explode('?', $path)[0];

$userId = $_GET['user_id'] ?? $_POST['user_id'] ?? null;
if (!$userId) {
    http_response_code(401);
    echo json_encode(['message' => 'Token requerido']);
    exit;
}

if ($method === 'GET' && $path === '') {
    getStudentGrades($userId);
} elseif ($method === 'POST' && $path === '') {
    createStudentGrade($userId);
} elseif ($method === 'PUT' && preg_match('/\/(\d+)/', $path, $matches)) {
    updateStudentGrade($userId, $matches[1]);
} elseif ($method === 'DELETE' && preg_match('/\/(\d+)/', $path, $matches)) {
    deleteStudentGrade($userId, $matches[1]);
} elseif ($method === 'GET' && $path === '/report') {
    getGradeReport($userId);
} else {
    http_response_code(404);
    echo json_encode(['message' => 'Endpoint no encontrado']);
}

function getStudentGrades($userId) {
    $organizationId = TenantManager::validateTenantRequest($userId);
    
    $studentId = $_GET['student_id'] ?? null;
    $subjectId = $_GET['subject_id'] ?? null;
    $gradeId = $_GET['grade_id'] ?? null;
    $period = $_GET['period'] ?? null;
    
    try {
        $pdo = getDBConnection();
        
        $sql = "
            SELECT sg.*, u.full_name as student_name, s.name as subject_name, 
                   ag.name as grade_name, a.description as achievement_description,
                   grader.full_name as graded_by_name
            FROM student_grades sg
            JOIN users u ON sg.student_id = u.id
            JOIN subjects s ON sg.subject_id = s.id
            JOIN academic_grades ag ON sg.grade_id = ag.id
            JOIN achievements a ON sg.achievement_id = a.id
            JOIN users grader ON sg.graded_by = grader.id
            WHERE sg.organization_id = ?
        ";
        $params = [$organizationId];
        
        if ($studentId) {
            $sql .= " AND sg.student_id = ?";
            $params[] = $studentId;
        }
        if ($subjectId) {
            $sql .= " AND sg.subject_id = ?";
            $params[] = $subjectId;
        }
        if ($gradeId) {
            $sql .= " AND sg.grade_id = ?";
            $params[] = $gradeId;
        }
        if ($period) {
            $sql .= " AND sg.period = ?";
            $params[] = $period;
        }
        
        $sql .= " ORDER BY u.full_name, s.name, sg.period, sg.graded_at DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $grades = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['grades' => $grades]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Error interno del servidor']);
    }
}

function createStudentGrade($userId) {
    $organizationId = TenantManager::validateTenantRequest($userId, 'teacher');
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['student_id']) || !isset($input['achievement_id']) || !isset($input['score'])) {
        http_response_code(400);
        echo json_encode(['message' => 'Estudiante, logro y calificación requeridos']);
        return;
    }
    
    $studentId = intval($input['student_id']);
    $achievementId = intval($input['achievement_id']);
    $score = floatval($input['score']);
    $observations = SecurityUtils::sanitizeInput($input['observations'] ?? '');
    
    // Determinar calificación cualitativa
    $qualitativeGrade = getQualitativeGrade($score);
    
    try {
        $pdo = getDBConnection();
        
        // Obtener datos del logro
        $stmt = $pdo->prepare("SELECT subject_id, grade_id, period FROM achievements WHERE id = ? AND organization_id = ?");
        $stmt->execute([$achievementId, $organizationId]);
        $achievement = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$achievement) {
            http_response_code(404);
            echo json_encode(['message' => 'Logro no encontrado']);
            return;
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO student_grades (organization_id, student_id, subject_id, achievement_id, grade_id, period, score, qualitative_grade, observations, graded_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            score = VALUES(score), qualitative_grade = VALUES(qualitative_grade), observations = VALUES(observations), graded_by = VALUES(graded_by), updated_at = CURRENT_TIMESTAMP
        ");
        $stmt->execute([
            $organizationId, $studentId, $achievement['subject_id'], $achievementId, 
            $achievement['grade_id'], $achievement['period'], $score, $qualitativeGrade, $observations, $userId
        ]);
        
        echo json_encode([
            'id' => $pdo->lastInsertId(),
            'qualitative_grade' => $qualitativeGrade,
            'message' => 'Calificación registrada exitosamente'
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Error interno del servidor']);
    }
}

function updateStudentGrade($userId, $gradeId) {
    $organizationId = TenantManager::validateTenantRequest($userId, 'teacher');
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $score = floatval($input['score']);
    $qualitativeGrade = getQualitativeGrade($score);
    $observations = SecurityUtils::sanitizeInput($input['observations'] ?? '');
    
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            UPDATE student_grades 
            SET score = ?, qualitative_grade = ?, observations = ?, graded_by = ?
            WHERE id = ? AND organization_id = ?
        ");
        $stmt->execute([$score, $qualitativeGrade, $observations, $userId, $gradeId, $organizationId]);
        
        echo json_encode(['message' => 'Calificación actualizada exitosamente']);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Error interno del servidor']);
    }
}

function deleteStudentGrade($userId, $gradeId) {
    $organizationId = TenantManager::validateTenantRequest($userId, 'teacher');
    
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("DELETE FROM student_grades WHERE id = ? AND organization_id = ?");
        $stmt->execute([$gradeId, $organizationId]);
        
        echo json_encode(['message' => 'Calificación eliminada exitosamente']);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Error interno del servidor']);
    }
}

function getGradeReport($userId) {
    $organizationId = TenantManager::validateTenantRequest($userId);
    
    $studentId = $_GET['student_id'] ?? null;
    $period = $_GET['period'] ?? null;
    
    if (!$studentId) {
        http_response_code(400);
        echo json_encode(['message' => 'ID del estudiante requerido']);
        return;
    }
    
    try {
        $pdo = getDBConnection();
        
        // Información del estudiante
        $stmt = $pdo->prepare("SELECT full_name, grade_level FROM users WHERE id = ? AND organization_id = ?");
        $stmt->execute([$studentId, $organizationId]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Calificaciones por asignatura
        $sql = "
            SELECT s.name as subject, s.area, 
                   AVG(sg.score) as average_score,
                   COUNT(sg.id) as total_grades,
                   GROUP_CONCAT(CONCAT(a.description, ': ', sg.score, ' (', sg.qualitative_grade, ')') SEPARATOR '; ') as grade_details
            FROM student_grades sg
            JOIN subjects s ON sg.subject_id = s.id
            JOIN achievements a ON sg.achievement_id = a.id
            WHERE sg.student_id = ? AND sg.organization_id = ?
        ";
        $params = [$studentId, $organizationId];
        
        if ($period) {
            $sql .= " AND sg.period = ?";
            $params[] = $period;
        }
        
        $sql .= " GROUP BY s.id ORDER BY s.area, s.name";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'student' => $student,
            'subjects' => $subjects,
            'period' => $period
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Error interno del servidor']);
    }
}

function getQualitativeGrade($score) {
    if ($score >= 9.0) return 'Superior';
    if ($score >= 7.0) return 'Alto';
    if ($score >= 6.0) return 'Básico';
    return 'Bajo';
}
?>