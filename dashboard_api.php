<?php
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$action = $_GET['action'] ?? '';

try {
    $pdo = getDBConnection();

    switch ($action) {
        case 'admin_stats':
            // Estadísticas para admin
            $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'");
            $total_students = $stmt->fetchColumn();

            $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'teacher'");
            $total_teachers = $stmt->fetchColumn();

            $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student' AND status = 'inactive'");
            $overdue_count = $stmt->fetchColumn();

            $total_debt = $overdue_count * 50000; // Simulación

            echo json_encode([
                'success' => true,
                'total_students' => $total_students,
                'total_teachers' => $total_teachers,
                'overdue_count' => $overdue_count,
                'total_debt' => $total_debt
            ]);
            break;

        case 'coordinator_stats':
            // Estadísticas para coordinadores
            $userId = $_GET['user_id'] ?? null;

            if (!$userId) {
                echo json_encode(['success' => false, 'message' => 'ID de usuario requerido']);
                break;
            }

            // Verificar usuario y obtener organización
            $stmt = $pdo->prepare("SELECT role, organization_id FROM users WHERE id = ? AND status = 'active'");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();

            if (!$user || !in_array($user['role'], ['coordinator', 'admin', 'super_admin'])) {
                echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
                break;
            }

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

            echo json_encode(['success' => true] + $stats);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>