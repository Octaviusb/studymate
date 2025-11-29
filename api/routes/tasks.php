<?php
require_once dirname(__DIR__) . '/config.php';

// Security headers
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
}

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$path = str_replace('/tasks', '', $_SERVER['REQUEST_URI']);
$path = explode('?', $path)[0];

if ($method === 'GET' && $path === '') {
    getTasks();
} elseif ($method === 'POST' && $path === '') {
    createTask();
} elseif ($method === 'PUT' && preg_match('/\/(\d+)/', $path, $matches)) {
    updateTask($matches[1]);
} elseif ($method === 'DELETE' && preg_match('/\/(\d+)/', $path, $matches)) {
    deleteTask($matches[1]);
} else {
    http_response_code(404);
    echo json_encode(['message' => 'Endpoint no encontrado']);
}

function getTasks() {
    $userId = filter_var($_GET['user_id'] ?? null, FILTER_VALIDATE_INT);

    if (!$userId || $userId <= 0) {
        http_response_code(400);
        echo json_encode(['message' => 'user_id válido requerido']);
        return;
    }
    
    require_once dirname(__DIR__) . '/utils/tenant.php';
    $organizationId = TenantManager::validateTenantRequest($userId);

    try {
        $pdo = getDBConnection();
        
        // Obtener rol del usuario
        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $userRole = $stmt->fetchColumn();
        
        if ($userRole === 'student') {
            // Estudiantes ven sus tareas asignadas
            $stmt = $pdo->prepare("
                SELECT t.*, st.completed, st.completion_date, st.grade, st.notes as student_notes,
                       c.name as class_name, u.full_name as teacher_name
                FROM student_tasks st
                JOIN tasks t ON st.task_id = t.id
                LEFT JOIN classes c ON t.class_id = c.id
                LEFT JOIN users u ON t.created_by = u.id
                WHERE st.student_id = ? AND t.organization_id = ?
                ORDER BY t.due_date ASC
            ");
            $stmt->execute([$userId, $organizationId]);
        } else {
            // Profesores/admins ven tareas que crearon
            $stmt = $pdo->prepare("
                SELECT t.*, c.name as class_name,
                       (SELECT COUNT(*) FROM student_tasks st WHERE st.task_id = t.id) as total_students,
                       (SELECT COUNT(*) FROM student_tasks st WHERE st.task_id = t.id AND st.completed = 1) as completed_students
                FROM tasks t
                LEFT JOIN classes c ON t.class_id = c.id
                WHERE t.created_by = ? AND t.organization_id = ?
                ORDER BY t.due_date ASC
            ");
            $stmt->execute([$userId, $organizationId]);
        }
        
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($userRole === 'student') {
            $stats = [
                'total' => count($tasks),
                'completed' => count(array_filter($tasks, fn($t) => $t['completed'])),
                'pending' => count(array_filter($tasks, fn($t) => !$t['completed'])),
                'high_priority' => count(array_filter($tasks, fn($t) => $t['priority'] === 'high' && !$t['completed']))
            ];
        } else {
            $stats = [
                'total_assignments' => count($tasks),
                'active_classes' => count(array_unique(array_column($tasks, 'class_id'))),
                'total_students_assigned' => array_sum(array_column($tasks, 'total_students')),
                'completion_rate' => count($tasks) > 0 ? round(array_sum(array_column($tasks, 'completed_students')) / array_sum(array_column($tasks, 'total_students')) * 100, 1) : 0
            ];
        }

        echo json_encode([
            'tasks' => $tasks,
            'stats' => $stats
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Error interno del servidor']);
    }
}

function createTask() {
    $rawInput = file_get_contents('php://input');
    if (strlen($rawInput) > 10240) {
        http_response_code(413);
        echo json_encode(['message' => 'Datos demasiado grandes']);
        return;
    }
    
    $input = json_decode($rawInput, true);

    if (!$input || !isset($input['title']) || !isset($input['user_id'])) {
        http_response_code(400);
        echo json_encode(['message' => 'Título y user_id requeridos']);
        return;
    }

    $title = trim(filter_var($input['title'], FILTER_SANITIZE_STRING));
    $subject = filter_var($input['subject'] ?? '', FILTER_SANITIZE_STRING);
    $description = filter_var($input['description'] ?? '', FILTER_SANITIZE_STRING);
    $dueDate = filter_var($input['dueDate'] ?? date('Y-m-d', strtotime('+1 day')), FILTER_SANITIZE_STRING);
    $priority = filter_var($input['priority'] ?? 'medium', FILTER_SANITIZE_STRING);
    $userId = filter_var($input['user_id'], FILTER_VALIDATE_INT);
    
    if (!$userId || $userId <= 0) {
        http_response_code(400);
        echo json_encode(['message' => 'user_id válido requerido']);
        return;
    }
    
    if (!in_array($priority, ['low', 'medium', 'high'])) {
        $priority = 'medium';
    }

    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("INSERT INTO tasks (user_id, title, subject, description, due_date, priority, completed) VALUES (?, ?, ?, ?, ?, ?, FALSE)");
        $stmt->execute([$userId, $title, $subject, $description, $dueDate, $priority]);

        $taskId = $pdo->lastInsertId();

        echo json_encode([
            'id' => $taskId,
            'user_id' => $userId,
            'title' => $title,
            'subject' => $subject,
            'description' => $description,
            'due_date' => $dueDate,
            'priority' => $priority,
            'completed' => false,
            'created_at' => date('Y-m-d H:i:s')
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Error interno del servidor']);
    }
}

function updateTask($taskId) {
    $taskId = filter_var($taskId, FILTER_VALIDATE_INT);
    if (!$taskId || $taskId <= 0) {
        http_response_code(400);
        echo json_encode(['message' => 'ID de tarea inválido']);
        return;
    }
    
    $rawInput = file_get_contents('php://input');
    if (strlen($rawInput) > 10240) {
        http_response_code(413);
        echo json_encode(['message' => 'Datos demasiado grandes']);
        return;
    }
    
    $input = json_decode($rawInput, true);

    if (!$input) {
        http_response_code(400);
        echo json_encode(['message' => 'Datos requeridos']);
        return;
    }

    try {
        $pdo = getDBConnection();

        // Build update query dynamically
        $updates = [];
        $params = [];

        if (isset($input['title'])) {
            $updates[] = "title = ?";
            $params[] = trim(filter_var($input['title'], FILTER_SANITIZE_STRING));
        }
        if (isset($input['subject'])) {
            $updates[] = "subject = ?";
            $params[] = filter_var($input['subject'], FILTER_SANITIZE_STRING);
        }
        if (isset($input['description'])) {
            $updates[] = "description = ?";
            $params[] = filter_var($input['description'], FILTER_SANITIZE_STRING);
        }
        if (isset($input['due_date'])) {
            $updates[] = "due_date = ?";
            $params[] = filter_var($input['due_date'], FILTER_SANITIZE_STRING);
        }
        if (isset($input['priority']) && in_array($input['priority'], ['low', 'medium', 'high'])) {
            $updates[] = "priority = ?";
            $params[] = $input['priority'];
        }
        if (isset($input['completed'])) {
            $updates[] = "completed = ?";
            $params[] = $input['completed'] ? 1 : 0;
        }

        if (empty($updates)) {
            http_response_code(400);
            echo json_encode(['message' => 'No hay campos para actualizar']);
            return;
        }

        $params[] = $taskId;
        $sql = "UPDATE tasks SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        echo json_encode(['message' => 'Tarea actualizada correctamente']);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Error interno del servidor']);
    }
}

function deleteTask($taskId) {
    $taskId = filter_var($taskId, FILTER_VALIDATE_INT);
    if (!$taskId || $taskId <= 0) {
        http_response_code(400);
        echo json_encode(['message' => 'ID de tarea inválido']);
        return;
    }
    
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
        $stmt->execute([$taskId]);

        echo json_encode(['message' => 'Tarea eliminada correctamente']);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Error interno del servidor']);
    }
}
?>
