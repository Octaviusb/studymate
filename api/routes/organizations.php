<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/utils/tenant.php';
require_once dirname(__DIR__) . '/utils/security.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$path = str_replace('/organizations', '', $_SERVER['REQUEST_URI']);
$path = explode('?', $path)[0];

$userId = $_GET['user_id'] ?? $_POST['user_id'] ?? null;
if (!$userId && $path !== '/public') {
    http_response_code(401);
    echo json_encode(['message' => 'Token requerido']);
    exit;
}

if ($method === 'GET' && $path === '') {
    getOrganizations($userId);
} elseif ($method === 'GET' && $path === '/public') {
    getPublicOrganizations();
} elseif ($method === 'POST' && $path === '') {
    createOrganization($userId);
} elseif ($method === 'GET' && $path === '/stats') {
    getOrganizationStats($userId);
} else {
    http_response_code(404);
    echo json_encode(['message' => 'Endpoint no encontrado']);
}

function getOrganizations($userId) {
    TenantManager::validateTenantRequest($userId, 'super_admin');
    
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            SELECT o.*, 
                   (SELECT COUNT(*) FROM users u WHERE u.organization_id = o.id) as user_count,
                   (SELECT COUNT(*) FROM classes c WHERE c.organization_id = o.id) as class_count
            FROM organizations o 
            ORDER BY o.created_at DESC
        ");
        $stmt->execute();
        $organizations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['organizations' => $organizations]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Error interno del servidor']);
    }
}

function createOrganization($userId) {
    TenantManager::validateTenantRequest($userId, 'super_admin');
    
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    
    if (!$input || !isset($input['name'])) {
        http_response_code(400);
        echo json_encode(['message' => 'Nombre de organización requerido']);
        return;
    }
    
    $name = SecurityUtils::sanitizeInput($input['name']);
    $domain = SecurityUtils::sanitizeInput($input['domain'] ?? '');
    $plan = in_array($input['plan'] ?? 'free', ['free', 'basic', 'premium']) ? $input['plan'] : 'free';
    $maxUsers = intval($input['max_users'] ?? 50);
    
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            INSERT INTO organizations (name, domain, plan, max_users, status) 
            VALUES (?, ?, ?, ?, 'active')
        ");
        $stmt->execute([$name, $domain, $plan, $maxUsers]);
        
        $orgId = $pdo->lastInsertId();
        
        echo json_encode([
            'id' => $orgId,
            'name' => $name,
            'domain' => $domain,
            'plan' => $plan,
            'max_users' => $maxUsers,
            'message' => 'Organización creada exitosamente'
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Error interno del servidor']);
    }
}

function getPublicOrganizations() {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT id, name, domain, plan FROM organizations WHERE status = 'active' ORDER BY name");
        $stmt->execute();
        $organizations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['organizations' => $organizations]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Error interno del servidor']);
    }
}

function getOrganizationStats($userId) {
    $organizationId = TenantManager::validateTenantRequest($userId, 'admin');
    
    try {
        $pdo = getDBConnection();
        
        // Stats básicas
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE organization_id = ?");
        $stmt->execute([$organizationId]);
        $totalUsers = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE organization_id = ? AND role = 'student'");
        $stmt->execute([$organizationId]);
        $totalStudents = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE organization_id = ? AND role = 'teacher'");
        $stmt->execute([$organizationId]);
        $totalTeachers = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM classes WHERE organization_id = ? AND status = 'active'");
        $stmt->execute([$organizationId]);
        $totalClasses = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE organization_id = ? AND task_type = 'assignment'");
        $stmt->execute([$organizationId]);
        $totalAssignments = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin_alerts WHERE organization_id = ? AND status = 'pending'");
        $stmt->execute([$organizationId]);
        $pendingAlerts = $stmt->fetchColumn();
        
        echo json_encode([
            'total_users' => $totalUsers,
            'total_students' => $totalStudents,
            'total_teachers' => $totalTeachers,
            'total_classes' => $totalClasses,
            'total_assignments' => $totalAssignments,
            'pending_alerts' => $pendingAlerts,
            'organization_id' => $organizationId
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Error interno del servidor']);
    }
}
?>