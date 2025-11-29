<?php
require_once dirname(__DIR__) . '/../config.php';
require_once dirname(__DIR__) . '/../utils/tenant.php';
require_once dirname(__DIR__) . '/../utils/security.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$path = str_replace('/grades', '', $_SERVER['REQUEST_URI']);
$path = explode('?', $path)[0];

$userId = $_GET['user_id'] ?? $_POST['user_id'] ?? null;
if (!$userId) {
    http_response_code(401);
    echo json_encode(['message' => 'Token requerido']);
    exit;
}

if ($method === 'GET' && $path === '') {
    getGrades($userId);
} elseif ($method === 'POST' && $path === '') {
    createGrade($userId);
} elseif ($method === 'PUT' && preg_match('/\/(\d+)/', $path, $matches)) {
    updateGrade($userId, $matches[1]);
} elseif ($method === 'DELETE' && preg_match('/\/(\d+)/', $path, $matches)) {
    deleteGrade($userId, $matches[1]);
} else {
    http_response_code(404);
    echo json_encode(['message' => 'Endpoint no encontrado']);
}

function getGrades($userId) {
    $organizationId = TenantManager::validateTenantRequest($userId);
    
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            SELECT ag.*, 
                   (SELECT COUNT(*) FROM users u WHERE u.grade_level = ag.name AND u.organization_id = ag.organization_id) as student_count
            FROM academic_grades ag
            WHERE ag.organization_id = ? AND ag.status = 'active'
            ORDER BY ag.sort_order, ag.name
        ");
        $stmt->execute([$organizationId]);
        $grades = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['grades' => $grades]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Error interno del servidor']);
    }
}

function createGrade($userId) {
    $organizationId = TenantManager::validateTenantRequest($userId, 'admin');
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['name'])) {
        http_response_code(400);
        echo json_encode(['message' => 'Nombre del grado requerido']);
        return;
    }
    
    $name = SecurityUtils::sanitizeInput($input['name']);
    $level = SecurityUtils::sanitizeInput($input['level'] ?? '');
    $description = SecurityUtils::sanitizeInput($input['description'] ?? '');
    $sortOrder = intval($input['sort_order'] ?? 0);
    
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            INSERT INTO academic_grades (organization_id, name, level, description, sort_order) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$organizationId, $name, $level, $description, $sortOrder]);
        
        echo json_encode([
            'id' => $pdo->lastInsertId(),
            'message' => 'Grado creado exitosamente'
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Error interno del servidor']);
    }
}

function updateGrade($userId, $gradeId) {
    $organizationId = TenantManager::validateTenantRequest($userId, 'admin');
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            UPDATE academic_grades 
            SET name = ?, level = ?, description = ?, sort_order = ?
            WHERE id = ? AND organization_id = ?
        ");
        $stmt->execute([
            SecurityUtils::sanitizeInput($input['name']),
            SecurityUtils::sanitizeInput($input['level'] ?? ''),
            SecurityUtils::sanitizeInput($input['description'] ?? ''),
            intval($input['sort_order'] ?? 0),
            $gradeId,
            $organizationId
        ]);
        
        echo json_encode(['message' => 'Grado actualizado exitosamente']);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Error interno del servidor']);
    }
}

function deleteGrade($userId, $gradeId) {
    $organizationId = TenantManager::validateTenantRequest($userId, 'admin');
    
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            UPDATE academic_grades 
            SET status = 'inactive' 
            WHERE id = ? AND organization_id = ?
        ");
        $stmt->execute([$gradeId, $organizationId]);
        
        echo json_encode(['message' => 'Grado eliminado exitosamente']);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Error interno del servidor']);
    }
}
?>