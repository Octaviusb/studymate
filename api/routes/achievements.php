<?php
require_once dirname(__DIR__) . '/../config.php';
require_once dirname(__DIR__) . '/../utils/tenant.php';
require_once dirname(__DIR__) . '/../utils/security.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$path = str_replace('/achievements', '', $_SERVER['REQUEST_URI']);
$path = explode('?', $path)[0];

$userId = $_GET['user_id'] ?? $_POST['user_id'] ?? null;
if (!$userId) {
    http_response_code(401);
    echo json_encode(['message' => 'Token requerido']);
    exit;
}

if ($method === 'GET' && $path === '') {
    getAchievements($userId);
} elseif ($method === 'POST' && $path === '') {
    createAchievement($userId);
} elseif ($method === 'PUT' && preg_match('/\/(\d+)/', $path, $matches)) {
    updateAchievement($userId, $matches[1]);
} elseif ($method === 'DELETE' && preg_match('/\/(\d+)/', $path, $matches)) {
    deleteAchievement($userId, $matches[1]);
} else {
    http_response_code(404);
    echo json_encode(['message' => 'Endpoint no encontrado']);
}

function getAchievements($userId) {
    $organizationId = TenantManager::validateTenantRequest($userId);
    
    $subjectId = $_GET['subject_id'] ?? null;
    $gradeId = $_GET['grade_id'] ?? null;
    $period = $_GET['period'] ?? null;
    
    try {
        $pdo = getDBConnection();
        
        $sql = "
            SELECT a.*, s.name as subject_name, ag.name as grade_name
            FROM achievements a
            JOIN subjects s ON a.subject_id = s.id
            JOIN academic_grades ag ON a.grade_id = ag.id
            WHERE a.organization_id = ? AND a.status = 'active'
        ";
        $params = [$organizationId];
        
        if ($subjectId) {
            $sql .= " AND a.subject_id = ?";
            $params[] = $subjectId;
        }
        if ($gradeId) {
            $sql .= " AND a.grade_id = ?";
            $params[] = $gradeId;
        }
        if ($period) {
            $sql .= " AND a.period = ?";
            $params[] = $period;
        }
        
        $sql .= " ORDER BY s.name, ag.sort_order, a.period, a.code";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $achievements = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['achievements' => $achievements]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Error interno del servidor']);
    }
}

function createAchievement($userId) {
    $organizationId = TenantManager::validateTenantRequest($userId, 'teacher');
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['description']) || !isset($input['subject_id']) || !isset($input['grade_id'])) {
        http_response_code(400);
        echo json_encode(['message' => 'Descripción, asignatura y grado requeridos']);
        return;
    }
    
    $subjectId = intval($input['subject_id']);
    $gradeId = intval($input['grade_id']);
    $period = in_array($input['period'], ['1', '2', '3', '4']) ? $input['period'] : '1';
    $code = SecurityUtils::sanitizeInput($input['code'] ?? '');
    $description = SecurityUtils::sanitizeInput($input['description']);
    $competencyType = in_array($input['competency_type'] ?? 'cognitive', ['cognitive', 'procedural', 'attitudinal']) ? $input['competency_type'] : 'cognitive';
    $weight = floatval($input['weight'] ?? 1.00);
    
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            INSERT INTO achievements (organization_id, subject_id, grade_id, period, code, description, competency_type, weight) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$organizationId, $subjectId, $gradeId, $period, $code, $description, $competencyType, $weight]);
        
        echo json_encode([
            'id' => $pdo->lastInsertId(),
            'message' => 'Logro creado exitosamente'
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Error interno del servidor']);
    }
}

function updateAchievement($userId, $achievementId) {
    $organizationId = TenantManager::validateTenantRequest($userId, 'teacher');
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            UPDATE achievements 
            SET subject_id = ?, grade_id = ?, period = ?, code = ?, description = ?, competency_type = ?, weight = ?
            WHERE id = ? AND organization_id = ?
        ");
        $stmt->execute([
            intval($input['subject_id']),
            intval($input['grade_id']),
            in_array($input['period'], ['1', '2', '3', '4']) ? $input['period'] : '1',
            SecurityUtils::sanitizeInput($input['code'] ?? ''),
            SecurityUtils::sanitizeInput($input['description']),
            in_array($input['competency_type'] ?? 'cognitive', ['cognitive', 'procedural', 'attitudinal']) ? $input['competency_type'] : 'cognitive',
            floatval($input['weight'] ?? 1.00),
            $achievementId,
            $organizationId
        ]);
        
        echo json_encode(['message' => 'Logro actualizado exitosamente']);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Error interno del servidor']);
    }
}

function deleteAchievement($userId, $achievementId) {
    $organizationId = TenantManager::validateTenantRequest($userId, 'teacher');
    
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            UPDATE achievements 
            SET status = 'inactive' 
            WHERE id = ? AND organization_id = ?
        ");
        $stmt->execute([$achievementId, $organizationId]);
        
        echo json_encode(['message' => 'Logro eliminado exitosamente']);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Error interno del servidor']);
    }
}
?>