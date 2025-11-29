<?php
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    $pdo = getDBConnection();
    
    switch ($action) {
        case 'create_task':
            if ($method !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            $stmt = $pdo->prepare("
                INSERT INTO tasks (organization_id, teacher_id, subject_id, grade_id, title, description, due_date, max_score) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([
                $input['organization_id'],
                $input['teacher_id'],
                $input['subject_id'],
                $input['grade_id'],
                $input['title'],
                $input['description'],
                $input['due_date'],
                $input['max_score'] ?? 10.00
            ]);
            
            if ($result) {
                $taskId = $pdo->lastInsertId();
                
                // Crear entregas para todos los estudiantes del grado
                $stmt = $pdo->prepare("
                    INSERT INTO task_submissions (task_id, student_id, status)
                    SELECT ?, u.id, 'pending'
                    FROM users u 
                    WHERE u.role = 'student' 
                    AND u.organization_id = ? 
                    AND u.grade_level = (SELECT name FROM academic_grades WHERE id = ?)
                ");
                
                $stmt->execute([$taskId, $input['organization_id'], $input['grade_id']]);
                
                echo json_encode(['success' => true, 'task_id' => $taskId]);
            } else {
                throw new Exception('Error creando tarea');
            }
            break;
            
        case 'get_student_tasks':
            $studentId = $_GET['student_id'] ?? 0;

            // Verificar si existen tareas, si no, devolver array vacío
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks");
            $stmt->execute();
            $taskCount = $stmt->fetchColumn();

            if ($taskCount == 0) {
                echo json_encode(['success' => true, 'tasks' => []]);
                break;
            }

            // Verificar si existe la tabla task_submissions, si no, devolver tareas sin estado
            $tableExists = $pdo->query("SHOW TABLES LIKE 'task_submissions'")->rowCount() > 0;

            if (!$tableExists) {
                $stmt = $pdo->prepare("
                    SELECT t.*, s.name as subject_name,
                           'pending' as submission_status,
                           NULL as score, NULL as submitted_at
                    FROM tasks t
                    JOIN subjects s ON t.subject_id = s.id
                    WHERE t.status = 'active'
                    ORDER BY t.due_date ASC
                ");
                $stmt->execute();
                $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $stmt = $pdo->prepare("
                    SELECT t.*, s.name as subject_name,
                           COALESCE(ts.status, 'pending') as submission_status,
                           ts.score, ts.submitted_at
                    FROM tasks t
                    JOIN subjects s ON t.subject_id = s.id
                    LEFT JOIN task_submissions ts ON t.id = ts.task_id AND ts.student_id = ?
                    WHERE t.status = 'active'
                    ORDER BY t.due_date ASC
                ");
                $stmt->execute([$studentId]);
                $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            echo json_encode(['success' => true, 'tasks' => $tasks]);
            break;
            
        case 'get_parent_tasks':
            $parentId = $_GET['parent_id'] ?? 0;
            
            // Obtener hijos del padre (simplificado - asumir relación por organización)
            $stmt = $pdo->prepare("
                SELECT t.*, s.name as subject_name, ts.status as submission_status, ts.score, ts.submitted_at, u.full_name as student_name
                FROM tasks t
                JOIN subjects s ON t.subject_id = s.id
                JOIN task_submissions ts ON t.id = ts.task_id
                JOIN users u ON ts.student_id = u.id
                WHERE u.organization_id = (SELECT organization_id FROM users WHERE id = ?) 
                AND u.role = 'student' AND t.status = 'active'
                ORDER BY t.due_date ASC
            ");
            
            $stmt->execute([$parentId]);
            $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'tasks' => $tasks]);
            break;
            
        case 'get_teacher_tasks':
            $teacherId = $_GET['teacher_id'] ?? 0;
            
            $stmt = $pdo->prepare("
                SELECT t.*, s.name as subject_name, ag.name as grade_name,
                       COUNT(ts.id) as total_students,
                       COUNT(CASE WHEN ts.status = 'submitted' THEN 1 END) as submitted_count
                FROM tasks t
                JOIN subjects s ON t.subject_id = s.id
                JOIN academic_grades ag ON t.grade_id = ag.id
                LEFT JOIN task_submissions ts ON t.id = ts.task_id
                WHERE t.teacher_id = ? AND t.status = 'active'
                GROUP BY t.id
                ORDER BY t.due_date ASC
            ");
            
            $stmt->execute([$teacherId]);
            $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'tasks' => $tasks]);
            break;
            
        default:
            throw new Exception('Acción no válida');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>