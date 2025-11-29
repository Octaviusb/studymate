<?php
require_once dirname(__DIR__) . '/../config.php';
require_once dirname(__DIR__) . '/../utils/tenant.php';
require_once dirname(__DIR__) . '/../utils/security.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$path = str_replace('/subjects', '', $_SERVER['REQUEST_URI']);
$path = explode('?', $path)[0];

$userId = $_GET['user_id'] ?? $_POST['user_id'] ?? null;
if (!$userId) {
    http_response_code(401);
    echo json_encode(['message' => 'Token requerido']);
    exit;
}

if ($method === 'GET' && $path === '') {
    getSubjects($userId);
} elseif ($method === 'POST' && $path === '') {
    createSubject($userId);
} elseif ($method === 'PUT' && preg_match('/\/(\d+)/', $path, $matches)) {
    updateSubject($userId, $matches[1]);
} elseif ($method === 'DELETE' && preg_match('/\/(\d+)/', $path, $matches)) {
    deleteSubject($userId, $matches[1]);
} else {
    http_response_code(404);
    echo json_encode(['message' => 'Endpoint no encontrado']);
}

function getSubjects($userId) {
    $organizationId = TenantManager::validateTenantRequest($userId);
    
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            SELECT * FROM subjects 
            WHERE organization_id = ? AND status = 'active'
            ORDER BY area, name
        ");
        $stmt->execute([$organizationId]);
        $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['subjects' => $subjects]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Error interno del servidor']);
    }
}

function createSubject($userId) {
    $organizationId = TenantManager::validateTenantRequest($userId, 'admin');
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['name'])) {
        http_response_code(400);
        echo json_encode(['message' => 'Nombre de asignatura requerido']);
        return;
    }
    
    $name = SecurityUtils::sanitizeInput($input['name']);
    $code = SecurityUtils::sanitizeInput($input['code'] ?? '');
    $area = SecurityUtils::sanitizeInput($input['area'] ?? '');
    $description = SecurityUtils::sanitizeInput($input['description'] ?? '');
    $hoursPerWeek = intval($input['hours_per_week'] ?? 2);
    
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            INSERT INTO subjects (organization_id, name, code, area, description, hours_per_week) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$organizationId, $name, $code, $area, $description, $hoursPerWeek]);
        
        echo json_encode([
            'id' => $pdo->lastInsertId(),
            'message' => 'Asignatura creada exitosamente'
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Error interno del servidor']);
    }
}

function updateSubject($userId, $subjectId) {
    $organizationId = TenantManager::validateTenantRequest($userId, 'admin');
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            UPDATE subjects 
            SET name = ?, code = ?, area = ?, description = ?, hours_per_week = ?
            WHERE id = ? AND organization_id = ?
        ");
        $stmt->execute([
            SecurityUtils::sanitizeInput($input['name']),
            SecurityUtils::sanitizeInput($input['code'] ?? ''),
            SecurityUtils::sanitizeInput($input['area'] ?? ''),
            SecurityUtils::sanitizeInput($input['description'] ?? ''),
            intval($input['hours_per_week'] ?? 2),
            $subjectId,
            $organizationId
        ]);
        
        echo json_encode(['message' => 'Asignatura actualizada exitosamente']);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Error interno del servidor']);
    }
}

function deleteSubject($userId, $subjectId) {
    $organizationId = TenantManager::validateTenantRequest($userId, 'admin');
    
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            UPDATE subjects 
            SET status = 'inactive' 
            WHERE id = ? AND organization_id = ?
        ");
        $stmt->execute([$subjectId, $organizationId]);
        
        echo json_encode(['message' => 'Asignatura eliminada exitosamente']);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Error interno del servidor']);
    }
}
?>