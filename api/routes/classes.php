<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/utils/tenant.php';
require_once dirname(__DIR__) . '/utils/security.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$path = str_replace('/classes', '', $_SERVER['REQUEST_URI']);
$path = explode('?', $path)[0];

// Obtener userId del token (simplificado)
$userId = $_GET['user_id'] ?? $_POST['user_id'] ?? null;
if (!$userId) {
    http_response_code(401);
    echo json_encode(['message' => 'Token requerido']);
    exit;
}

if ($method === 'GET' && $path === '') {
    getClasses($userId);
} elseif ($method === 'POST' && $path === '') {
    createClass($userId);
} elseif ($method === 'GET' && preg_match('/\/(\d+)\/students/', $path, $matches)) {
    getClassStudents($userId, $matches[1]);
} elseif ($method === 'POST' && preg_match('/\/(\d+)\/students/', $path, $matches)) {
    addStudentToClass($userId, $matches[1]);
} elseif ($method === 'POST' && preg_match('/\/(\d+)\/assignments/', $path, $matches)) {
    createClassAssignment($userId, $matches[1]);
} else {
    http_response_code(404);
    echo json_encode(['message' => 'Endpoint no encontrado']);
}

function getClasses($userId) {
    $organizationId = TenantManager::validateTenantRequest($userId);
    
    try {
        $pdo = getDBConnection();
        
        // Si es teacher, solo sus clases. Si es admin, todas las clases
        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $userRole = $stmt->fetchColumn();
        
        if ($userRole === 'teacher') {
            $stmt = $pdo->prepare("
                SELECT c.*, u.full_name as teacher_name,
                       (SELECT COUNT(*) FROM class_students cs WHERE cs.class_id = c.id) as student_count
                FROM classes c 
                JOIN users u ON c.teacher_id = u.id 
                WHERE c.organization_id = ? AND c.teacher_id = ? AND c.status = 'active'
                ORDER BY c.name
            ");
            $stmt->execute([$organizationId, $userId]);
        } else {
            $stmt = $pdo->prepare("
                SELECT c.*, u.full_name as teacher_name,
                       (SELECT COUNT(*) FROM class_students cs WHERE cs.class_id = c.id) as student_count
                FROM classes c 
                JOIN users u ON c.teacher_id = u.id 
                WHERE c.organization_id = ? AND c.status = 'active'
                ORDER BY c.name
            ");
            $stmt->execute([$organizationId]);
        }
        
        $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['classes' => $classes]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Error interno del servidor']);
    }
}

function createClass($userId) {
    $organizationId = TenantManager::validateTenantRequest($userId, 'teacher');
    
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    
    if (!$input || !isset($input['name']) || !isset($input['subject'])) {
        http_response_code(400);
        echo json_encode(['message' => 'Nombre y materia requeridos']);
        return;
    }
    
    $name = SecurityUtils::sanitizeInput($input['name']);
    $subject = SecurityUtils::sanitizeInput($input['subject']);
    $gradeLevel = SecurityUtils::sanitizeInput($input['grade_level'] ?? '');
    $description = SecurityUtils::sanitizeInput($input['description'] ?? '');
    
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            INSERT INTO classes (organization_id, teacher_id, name, subject, grade_level, description) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$organizationId, $userId, $name, $subject, $gradeLevel, $description]);
        
        $classId = $pdo->lastInsertId();
        
        echo json_encode([
            'id' => $classId,
            'name' => $name,
            'subject' => $subject,
            'grade_level' => $gradeLevel,
            'description' => $description,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Error interno del servidor']);
    }
}

function getClassStudents($userId, $classId) {
    $organizationId = TenantManager::validateTenantRequest($userId, 'teacher');
    
    try {
        $pdo = getDBConnection();
        
        // Verificar que la clase pertenece a la organización
        $stmt = $pdo->prepare("SELECT teacher_id FROM classes WHERE id = ? AND organization_id = ?");
        $stmt->execute([$classId, $organizationId]);
        $teacherId = $stmt->fetchColumn();
        
        if (!$teacherId) {
            http_response_code(404);
            echo json_encode(['message' => 'Clase no encontrada']);
            return;
        }
        
        // Obtener estudiantes de la clase
        $stmt = $pdo->prepare("
            SELECT u.id, u.full_name, u.email, u.grade_level, cs.enrolled_at
            FROM class_students cs
            JOIN users u ON cs.student_id = u.id
            WHERE cs.class_id = ? AND u.role = 'student'
            ORDER BY u.full_name
        ");
        $stmt->execute([$classId]);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['students' => $students]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Error interno del servidor']);
    }
}

function addStudentToClass($userId, $classId) {
    $organizationId = TenantManager::validateTenantRequest($userId, 'teacher');
    
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    
    if (!$input || !isset($input['student_email'])) {
        http_response_code(400);
        echo json_encode(['message' => 'Email del estudiante requerido']);
        return;
    }
    
    $studentEmail = SecurityUtils::sanitizeInput($input['student_email'], 'email');
    
    try {
        $pdo = getDBConnection();
        
        // Buscar estudiante en la misma organización
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND organization_id = ? AND role = 'student'");
        $stmt->execute([$studentEmail, $organizationId]);
        $studentId = $stmt->fetchColumn();
        
        if (!$studentId) {
            http_response_code(404);
            echo json_encode(['message' => 'Estudiante no encontrado en esta organización']);
            return;
        }
        
        // Agregar a la clase
        $stmt = $pdo->prepare("INSERT IGNORE INTO class_students (class_id, student_id) VALUES (?, ?)");
        $stmt->execute([$classId, $studentId]);
        
        echo json_encode(['message' => 'Estudiante agregado a la clase']);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Error interno del servidor']);
    }
}

function createClassAssignment($userId, $classId) {
    $organizationId = TenantManager::validateTenantRequest($userId, 'teacher');
    
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    
    if (!$input || !isset($input['title'])) {
        http_response_code(400);
        echo json_encode(['message' => 'Título de la tarea requerido']);
        return;
    }
    
    $title = SecurityUtils::sanitizeInput($input['title']);
    $subject = SecurityUtils::sanitizeInput($input['subject'] ?? '');
    $description = SecurityUtils::sanitizeInput($input['description'] ?? '');
    $dueDate = SecurityUtils::sanitizeInput($input['due_date'] ?? date('Y-m-d', strtotime('+1 week')));
    $priority = in_array($input['priority'] ?? 'medium', ['low', 'medium', 'high']) ? $input['priority'] : 'medium';
    
    try {
        $pdo = getDBConnection();
        
        // Crear la tarea de clase
        $stmt = $pdo->prepare("
            INSERT INTO tasks (organization_id, created_by, class_id, title, subject, description, due_date, priority, task_type) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'assignment')
        ");
        $stmt->execute([$organizationId, $userId, $classId, $title, $subject, $description, $dueDate, $priority]);
        
        $taskId = $pdo->lastInsertId();
        
        // Asignar a todos los estudiantes de la clase
        $stmt = $pdo->prepare("
            INSERT INTO student_tasks (task_id, student_id)
            SELECT ?, cs.student_id 
            FROM class_students cs 
            WHERE cs.class_id = ?
        ");
        $stmt->execute([$taskId, $classId]);
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM student_tasks WHERE task_id = ?");
        $stmt->execute([$taskId]);
        $studentsAssigned = $stmt->fetchColumn();
        
        echo json_encode([
            'task_id' => $taskId,
            'title' => $title,
            'students_assigned' => $studentsAssigned,
            'due_date' => $dueDate,
            'message' => 'Tarea asignada a todos los estudiantes de la clase'
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Error interno del servidor']);
    }
}
?>