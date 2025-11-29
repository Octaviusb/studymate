<?php
require_once dirname(__DIR__) . '/../config.php';
require_once dirname(__DIR__) . '/../utils/tenant.php';
require_once dirname(__DIR__) . '/../utils/security.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$path = str_replace('/reports', '', $_SERVER['REQUEST_URI']);
$path = explode('?', $path)[0];

$userId = $_GET['user_id'] ?? $_POST['user_id'] ?? null;
if (!$userId) {
    http_response_code(401);
    echo json_encode(['message' => 'Token requerido']);
    exit;
}

if ($method === 'GET' && $path === '/student') {
    getStudentReport($userId);
} elseif ($method === 'GET' && $path === '/grade') {
    getGradeReport($userId);
} elseif ($method === 'GET' && $path === '/subject') {
    getSubjectReport($userId);
} elseif ($method === 'GET' && $path === '/teacher') {
    getTeacherReport($userId);
} elseif ($method === 'GET' && $path === '/achievements') {
    getAchievementsReport($userId);
} else {
    http_response_code(404);
    echo json_encode(['message' => 'Endpoint no encontrado']);
}

function getStudentReport($userId) {
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
        $stmt = $pdo->prepare("
            SELECT u.full_name, u.email, u.grade_level, o.name as organization_name
            FROM users u 
            JOIN organizations o ON u.organization_id = o.id
            WHERE u.id = ? AND u.organization_id = ?
        ");
        $stmt->execute([$studentId, $organizationId]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Calificaciones detalladas
        $sql = "
            SELECT s.name as subject, s.area, a.description as achievement, 
                   sg.score, sg.qualitative_grade, sg.observations, sg.graded_at,
                   grader.full_name as teacher_name
            FROM student_grades sg
            JOIN subjects s ON sg.subject_id = s.id
            JOIN achievements a ON sg.achievement_id = a.id
            JOIN users grader ON sg.graded_by = grader.id
            WHERE sg.student_id = ? AND sg.organization_id = ?
        ";
        $params = [$studentId, $organizationId];
        
        if ($period) {
            $sql .= " AND sg.period = ?";
            $params[] = $period;
        }
        
        $sql .= " ORDER BY s.area, s.name, sg.graded_at DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $grades = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Promedios por asignatura
        $sql = "
            SELECT s.name as subject, s.area, 
                   AVG(sg.score) as average_score,
                   COUNT(sg.id) as total_evaluations
            FROM student_grades sg
            JOIN subjects s ON sg.subject_id = s.id
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
        $averages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'student' => $student,
            'grades' => $grades,
            'averages' => $averages,
            'period' => $period,
            'generated_at' => date('Y-m-d H:i:s')
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Error interno del servidor']);
    }
}

function getGradeReport($userId) {
    $organizationId = TenantManager::validateTenantRequest($userId);
    
    $gradeId = $_GET['grade_id'] ?? null;
    $period = $_GET['period'] ?? null;
    
    if (!$gradeId) {
        http_response_code(400);
        echo json_encode(['message' => 'ID del grado requerido']);
        return;
    }
    
    try {
        $pdo = getDBConnection();
        
        // Información del grado
        $stmt = $pdo->prepare("SELECT name, level, description FROM academic_grades WHERE id = ? AND organization_id = ?");
        $stmt->execute([$gradeId, $organizationId]);
        $grade = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Estudiantes del grado con promedios
        $sql = "
            SELECT u.id, u.full_name, u.email,
                   AVG(sg.score) as general_average,
                   COUNT(DISTINCT sg.subject_id) as subjects_count
            FROM users u
            LEFT JOIN student_grades sg ON u.id = sg.student_id AND sg.grade_id = ?
            WHERE u.organization_id = ? AND u.role = 'student' AND u.grade_level = ?
        ";
        $params = [$gradeId, $organizationId, $grade['name']];
        
        if ($period) {
            $sql .= " AND (sg.period = ? OR sg.period IS NULL)";
            $params[] = $period;
        }
        
        $sql .= " GROUP BY u.id ORDER BY u.full_name";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Estadísticas por asignatura
        $sql = "
            SELECT s.name as subject, s.area,
                   AVG(sg.score) as average_score,
                   MIN(sg.score) as min_score,
                   MAX(sg.score) as max_score,
                   COUNT(sg.id) as total_grades
            FROM student_grades sg
            JOIN subjects s ON sg.subject_id = s.id
            WHERE sg.grade_id = ? AND sg.organization_id = ?
        ";
        $params = [$gradeId, $organizationId];
        
        if ($period) {
            $sql .= " AND sg.period = ?";
            $params[] = $period;
        }
        
        $sql .= " GROUP BY s.id ORDER BY s.area, s.name";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'grade' => $grade,
            'students' => $students,
            'subjects' => $subjects,
            'period' => $period,
            'generated_at' => date('Y-m-d H:i:s')
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Error interno del servidor']);
    }
}

function getSubjectReport($userId) {
    $organizationId = TenantManager::validateTenantRequest($userId);
    
    $subjectId = $_GET['subject_id'] ?? null;
    $period = $_GET['period'] ?? null;
    
    if (!$subjectId) {
        http_response_code(400);
        echo json_encode(['message' => 'ID de la asignatura requerido']);
        return;
    }
    
    try {
        $pdo = getDBConnection();
        
        // Información de la asignatura
        $stmt = $pdo->prepare("SELECT name, code, area, description FROM subjects WHERE id = ? AND organization_id = ?");
        $stmt->execute([$subjectId, $organizationId]);
        $subject = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Calificaciones por grado
        $sql = "
            SELECT ag.name as grade, 
                   AVG(sg.score) as average_score,
                   COUNT(DISTINCT sg.student_id) as students_count,
                   COUNT(sg.id) as total_grades
            FROM student_grades sg
            JOIN academic_grades ag ON sg.grade_id = ag.id
            WHERE sg.subject_id = ? AND sg.organization_id = ?
        ";
        $params = [$subjectId, $organizationId];
        
        if ($period) {
            $sql .= " AND sg.period = ?";
            $params[] = $period;
        }
        
        $sql .= " GROUP BY ag.id ORDER BY ag.sort_order";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $gradeStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Logros de la asignatura
        $sql = "
            SELECT a.description, a.period, a.competency_type,
                   AVG(sg.score) as average_score,
                   COUNT(sg.id) as evaluations_count
            FROM achievements a
            LEFT JOIN student_grades sg ON a.id = sg.achievement_id
            WHERE a.subject_id = ? AND a.organization_id = ?
        ";
        $params = [$subjectId, $organizationId];
        
        if ($period) {
            $sql .= " AND a.period = ?";
            $params[] = $period;
        }
        
        $sql .= " GROUP BY a.id ORDER BY a.period, a.code";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $achievements = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'subject' => $subject,
            'grade_statistics' => $gradeStats,
            'achievements' => $achievements,
            'period' => $period,
            'generated_at' => date('Y-m-d H:i:s')
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Error interno del servidor']);
    }
}

function getTeacherReport($userId) {
    $organizationId = TenantManager::validateTenantRequest($userId);
    
    $teacherId = $_GET['teacher_id'] ?? $userId;
    
    try {
        $pdo = getDBConnection();
        
        // Información del docente
        $stmt = $pdo->prepare("SELECT full_name, email FROM users WHERE id = ? AND organization_id = ?");
        $stmt->execute([$teacherId, $organizationId]);
        $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Asignaturas que enseña
        $stmt = $pdo->prepare("
            SELECT DISTINCT s.name, s.area, s.code
            FROM student_grades sg
            JOIN subjects s ON sg.subject_id = s.id
            WHERE sg.graded_by = ? AND sg.organization_id = ?
            ORDER BY s.area, s.name
        ");
        $stmt->execute([$teacherId, $organizationId]);
        $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Estadísticas de calificaciones
        $stmt = $pdo->prepare("
            SELECT COUNT(sg.id) as total_grades,
                   AVG(sg.score) as average_grade,
                   COUNT(DISTINCT sg.student_id) as students_graded,
                   COUNT(DISTINCT sg.subject_id) as subjects_taught
            FROM student_grades sg
            WHERE sg.graded_by = ? AND sg.organization_id = ?
        ");
        $stmt->execute([$teacherId, $organizationId]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'teacher' => $teacher,
            'subjects' => $subjects,
            'statistics' => $stats,
            'generated_at' => date('Y-m-d H:i:s')
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Error interno del servidor']);
    }
}

function getAchievementsReport($userId) {
    $organizationId = TenantManager::validateTenantRequest($userId);
    
    $period = $_GET['period'] ?? null;
    $subjectId = $_GET['subject_id'] ?? null;
    
    try {
        $pdo = getDBConnection();
        
        $sql = "
            SELECT a.description, a.period, a.competency_type, a.weight,
                   s.name as subject_name, s.area,
                   ag.name as grade_name,
                   AVG(sg.score) as average_score,
                   COUNT(sg.id) as evaluations_count,
                   COUNT(CASE WHEN sg.qualitative_grade = 'Superior' THEN 1 END) as superior_count,
                   COUNT(CASE WHEN sg.qualitative_grade = 'Alto' THEN 1 END) as alto_count,
                   COUNT(CASE WHEN sg.qualitative_grade = 'Básico' THEN 1 END) as basico_count,
                   COUNT(CASE WHEN sg.qualitative_grade = 'Bajo' THEN 1 END) as bajo_count
            FROM achievements a
            JOIN subjects s ON a.subject_id = s.id
            JOIN academic_grades ag ON a.grade_id = ag.id
            LEFT JOIN student_grades sg ON a.id = sg.achievement_id
            WHERE a.organization_id = ?
        ";
        $params = [$organizationId];
        
        if ($period) {
            $sql .= " AND a.period = ?";
            $params[] = $period;
        }
        
        if ($subjectId) {
            $sql .= " AND a.subject_id = ?";
            $params[] = $subjectId;
        }
        
        $sql .= " GROUP BY a.id ORDER BY s.area, s.name, a.period, a.code";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $achievements = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'achievements' => $achievements,
            'period' => $period,
            'subject_id' => $subjectId,
            'generated_at' => date('Y-m-d H:i:s')
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Error interno del servidor']);
    }
}
?>