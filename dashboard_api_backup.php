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
    
    // Verificar usuario y obtener organización
    $stmt = $pdo->prepare("SELECT role, organization_id FROM users WHERE id = ? AND status = 'active'");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        http_response_code(403);
        echo json_encode(['message' => 'Usuario no encontrado']);
        exit;
    }
    
    if ($action === 'teacher_stats') {
        // Estadísticas para docentes
        $stats = [];
        
        // Mis clases
        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT s.id) FROM subjects s WHERE s.organization_id = ?");
        $stmt->execute([$user['organization_id']]);
        $stats['my_classes'] = $stmt->fetchColumn() ?: 0;
        
        // Mis estudiantes
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'student' AND organization_id = ?");
        $stmt->execute([$user['organization_id']]);
        $stats['my_students'] = $stmt->fetchColumn() ?: 0;
        
        // Calificaciones pendientes
        $stats['pending_grades'] = 5; // Simulado
        
        // Rendimiento promedio
        $stmt = $pdo->prepare("SELECT AVG(sg.numeric_grade) FROM student_grades sg WHERE sg.organization_id = ?");
        $stmt->execute([$user['organization_id']]);
        $avgGrade = $stmt->fetchColumn();
        $stats['avg_performance'] = $avgGrade ? round(($avgGrade / 10) * 100) : 0;
        
        echo json_encode($stats);
        
    } elseif ($action === 'student_stats') {
        // Estadísticas para estudiantes
        $stats = [];
        
        // Promedio actual
        $stmt = $pdo->prepare("SELECT AVG(sg.numeric_grade) FROM student_grades sg WHERE sg.student_id = ?");
        $stmt->execute([$userId]);
        $stats['current_average'] = round($stmt->fetchColumn() ?: 0, 1);
        
        // Materias cursando
        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT s.id) FROM subjects s WHERE s.organization_id = ?");
        $stmt->execute([$user['organization_id']]);
        $stats['subjects_count'] = $stmt->fetchColumn() ?: 0;
        
        // Asistencia (simulado)
        $stats['attendance_rate'] = 95;
        
        // Tareas pendientes (simulado)
        $stats['pending_assignments'] = 2;
        
        echo json_encode($stats);
        
    } elseif ($action === 'parent_stats') {
        // Estadísticas para padres
        $stats = [];
        
        // Hijos registrados
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM parent_student_relations WHERE parent_id = ?");
        $stmt->execute([$userId]);
        $stats['total_children'] = $stmt->fetchColumn() ?: 0;
        
        // Promedio familiar
        $stmt = $pdo->prepare("
            SELECT AVG(sg.numeric_grade) 
            FROM student_grades sg 
            JOIN parent_student_relations psr ON sg.student_id = psr.student_id 
            WHERE psr.parent_id = ?
        ");
        $stmt->execute([$userId]);
        $stats['avg_grades'] = round($stmt->fetchColumn() ?: 0, 1);
        
        // Pagos pendientes (simulado)
        $stats['pending_payments'] = 1;
        
        // Mensajes sin leer (simulado)
        $stats['unread_messages'] = 3;
        
        echo json_encode($stats);
        
    } elseif ($action === 'coordinator_stats') {
        // Estadísticas para coordinadores
        $stats = [];
        
        // Docentes activos
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'teacher' AND organization_id = ? AND status = 'active'");
        $stmt->execute([$user['organization_id']]);
        $stats['total_teachers'] = $stmt->fetchColumn() ?: 0;
        
        // Total materias
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM subjects WHERE organization_id = ?");
        $stmt->execute([$user['organization_id']]);
        $stats['total_subjects'] = $stmt->fetchColumn() ?: 0;
        
        // Rendimiento promedio
        $stmt = $pdo->prepare("SELECT AVG(sg.numeric_grade) FROM student_grades sg WHERE sg.organization_id = ?");
        $stmt->execute([$user['organization_id']]);
        $avgGrade = $stmt->fetchColumn();
        $stats['avg_performance'] = $avgGrade ? round(($avgGrade / 10) * 100) : 0;
        
        // Evaluaciones pendientes (simulado)
        $stats['pending_evaluations'] = 3;
        
        echo json_encode($stats);
        
    } elseif ($action === 'admin_stats') {
        // Estadísticas para administradores
        $stats = [];
        
        // Estudiantes
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'student' AND organization_id = ? AND status = 'active'");
        $stmt->execute([$user['organization_id']]);
        $stats['total_students'] = $stmt->fetchColumn() ?: 0;
        
        // Docentes
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'teacher' AND organization_id = ? AND status = 'active'");
        $stmt->execute([$user['organization_id']]);
        $stats['total_teachers'] = $stmt->fetchColumn() ?: 0;
        
        // Estudiantes en mora (simulado)
        $stats['overdue_count'] = 3;
        
        // Cartera vencida (simulado)
        $stats['total_debt'] = 450000;
        
        echo json_encode($stats);
        
    } else {
        http_response_code(404);
        echo json_encode(['message' => 'Acción no encontrada']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Error interno del servidor']);
}
?>