<?php
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$action = $_GET['action'] ?? '';

try {
    $pdo = getDBConnection();
    
    if ($action === 'get_users') {
        $orgId = $_GET['organization_id'] ?? 1;
        $role = $_GET['role'] ?? '';
        
        $sql = "SELECT id, username, email, full_name, role, grade_level, status, created_at FROM users WHERE organization_id = ?";
        $params = [$orgId];
        
        if ($role) {
            $sql .= " AND role = ?";
            $params[] = $role;
        }
        
        $sql .= " ORDER BY full_name ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'users' => $users]);
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>