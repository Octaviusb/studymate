<?php
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$action = $_GET['action'] ?? '';

try {
    $pdo = getDBConnection();
    
    switch ($action) {
        case 'academic_report':
            $orgId = $_GET['organization_id'] ?? 0;
            $period = $_GET['period'] ?? 'current';
            
            // Reporte académico general
            $stmt = $pdo->prepare("
                SELECT 
                    u.full_name as student_name,
                    u.grade_level,
                    s.name as subject_name,
                    AVG(sg.score) as average_score,
                    COUNT(sg.id) as total_grades
                FROM users u
                LEFT JOIN student_grades sg ON u.id = sg.student_id
                LEFT JOIN subjects s ON sg.subject_id = s.id
                WHERE u.organization_id = ? AND u.role = 'student'
                GROUP BY u.id, s.id
                ORDER BY u.full_name, s.name
            ");
            
            $stmt->execute([$orgId]);
            $report = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'report' => $report]);
            break;
            
        case 'student_report':
            $studentId = $_GET['student_id'] ?? 0;
            
            $stmt = $pdo->prepare("
                SELECT 
                    s.name as subject_name,
                    sg.score,
                    sg.qualitative_grade,
                    sg.observations,
                    sg.graded_at
                FROM student_grades sg
                JOIN subjects s ON sg.subject_id = s.id
                WHERE sg.student_id = ?
                ORDER BY s.name, sg.graded_at DESC
            ");
            
            $stmt->execute([$studentId]);
            $grades = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'grades' => $grades]);
            break;
            
        case 'attendance_report':
            $orgId = $_GET['organization_id'] ?? 0;
            
            // Simulación de reporte de asistencia
            $report = [
                ['student' => 'Ana García', 'attendance' => '95%', 'absences' => 3],
                ['student' => 'Carlos López', 'attendance' => '88%', 'absences' => 7],
                ['student' => 'María Rodríguez', 'attendance' => '98%', 'absences' => 1]
            ];
            
            echo json_encode(['success' => true, 'report' => $report]);
            break;
            
        default:
            throw new Exception('Acción no válida');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>