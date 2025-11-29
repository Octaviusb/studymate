<?php
require_once 'config.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$userId = $_GET['user_id'] ?? null;

if (!$userId) {
    http_response_code(400);
    echo json_encode(['message' => 'ID de usuario requerido']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Verificar que es super admin
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ? AND role = 'super_admin'");
    $stmt->execute([$userId]);
    if (!$stmt->fetchColumn()) {
        http_response_code(403);
        echo json_encode(['message' => 'Acceso denegado']);
        exit;
    }
    
    if ($action === 'subjects') {
        $orgFilter = $_GET['org_id'] ?? '';
        
        $sql = "SELECT s.*, o.name as organization_name 
                FROM subjects s 
                JOIN organizations o ON s.organization_id = o.id 
                WHERE 1=1";
        $params = [];
        
        if ($orgFilter) {
            $sql .= " AND s.organization_id = ?";
            $params[] = $orgFilter;
        }
        
        $sql .= " ORDER BY o.name, s.name";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $subjects = $stmt->fetchAll();
        
        echo json_encode(['subjects' => $subjects]);
        
    } elseif ($action === 'grades') {
        $orgFilter = $_GET['org_id'] ?? '';
        
        $sql = "SELECT ag.*, o.name as organization_name,
                       (SELECT COUNT(*) FROM users u WHERE u.organization_id = ag.organization_id AND u.role = 'student') as student_count
                FROM academic_grades ag 
                JOIN organizations o ON ag.organization_id = o.id 
                WHERE 1=1";
        $params = [];
        
        if ($orgFilter) {
            $sql .= " AND ag.organization_id = ?";
            $params[] = $orgFilter;
        }
        
        $sql .= " ORDER BY o.name, ag.grade_order";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $grades = $stmt->fetchAll();
        
        echo json_encode(['grades' => $grades]);
        
    } elseif ($action === 'recent_grades') {
        $orgFilter = $_GET['org_id'] ?? '';
        $limit = $_GET['limit'] ?? 50;
        
        $sql = "SELECT sg.*, u.full_name as student_name, s.name as subject_name, 
                       ag.name as grade_name, o.name as organization_name
                FROM student_grades sg
                JOIN users u ON sg.student_id = u.id
                JOIN subjects s ON sg.subject_id = s.id
                JOIN academic_grades ag ON sg.grade_id = ag.id
                JOIN organizations o ON sg.organization_id = o.id
                WHERE 1=1";
        $params = [];
        
        if ($orgFilter) {
            $sql .= " AND sg.organization_id = ?";
            $params[] = $orgFilter;
        }
        
        $sql .= " ORDER BY sg.created_at DESC LIMIT ?";
        $params[] = (int)$limit;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $recentGrades = $stmt->fetchAll();
        
        echo json_encode(['recent_grades' => $recentGrades]);
        
    } elseif ($action === 'filters') {
        // Obtener datos para filtros
        $filters = [];
        
        // Organizaciones
        $stmt = $pdo->query("SELECT id, name FROM organizations WHERE status = 'active' ORDER BY name");
        $filters['organizations'] = $stmt->fetchAll();
        
        // Grados
        $stmt = $pdo->query("SELECT DISTINCT ag.id, ag.name, o.name as org_name 
                            FROM academic_grades ag 
                            JOIN organizations o ON ag.organization_id = o.id 
                            ORDER BY o.name, ag.grade_order");
        $filters['grades'] = $stmt->fetchAll();
        
        // Materias
        $stmt = $pdo->query("SELECT DISTINCT s.id, s.name, o.name as org_name 
                            FROM subjects s 
                            JOIN organizations o ON s.organization_id = o.id 
                            ORDER BY o.name, s.name");
        $filters['subjects'] = $stmt->fetchAll();
        
        echo json_encode(['filters' => $filters]);
        
    } else {
        http_response_code(404);
        echo json_encode(['message' => 'Acción no encontrada']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Error interno del servidor']);
}
?>