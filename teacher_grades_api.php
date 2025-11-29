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
    
    // Verificar que es docente
    $stmt = $pdo->prepare("SELECT role, organization_id FROM users WHERE id = ? AND role = 'teacher'");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        http_response_code(403);
        echo json_encode(['message' => 'Acceso denegado']);
        exit;
    }
    
    if ($action === 'save_grades') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $subjectId = $input['subject_id'] ?? null;
        $periodId = $input['period_id'] ?? null;
        $evaluationType = $input['evaluation_type'] ?? 'quiz';
        $grades = $input['grades'] ?? [];
        
        if (!$subjectId || !$periodId || empty($grades)) {
            http_response_code(400);
            echo json_encode(['message' => 'Datos incompletos']);
            exit;
        }
        
        $savedCount = 0;
        
        foreach ($grades as $grade) {
            $studentId = $grade['student_id'];
            $numericGrade = $grade['numeric_grade'];
            $observations = $grade['observations'] ?? '';
            
            if ($numericGrade < 0 || $numericGrade > 10) {
                continue;
            }
            
            // Convertir a calificación cualitativa
            if ($numericGrade >= 9.0) {
                $qualitativeGrade = 'Superior';
            } elseif ($numericGrade >= 7.0) {
                $qualitativeGrade = 'Alto';
            } elseif ($numericGrade >= 6.0) {
                $qualitativeGrade = 'Básico';
            } else {
                $qualitativeGrade = 'Bajo';
            }
            
            // Insertar calificación
            $stmt = $pdo->prepare("
                INSERT INTO student_grades 
                (organization_id, student_id, subject_id, grade_id, teacher_id, period_id, 
                 numeric_grade, qualitative_grade, evaluation_type, observations, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $user['organization_id'],
                $studentId,
                $subjectId,
                1, // grade_id por defecto
                $userId,
                $periodId,
                $numericGrade,
                $qualitativeGrade,
                $evaluationType,
                $observations,
                $userId
            ]);
            
            $savedCount++;
        }
        
        echo json_encode([
            'message' => "Se guardaron $savedCount calificaciones exitosamente",
            'saved_count' => $savedCount
        ]);
        
    } elseif ($action === 'preview_grades') {
        $input = json_decode(file_get_contents('php://input'), true);
        $grades = $input['grades'] ?? [];
        
        $preview = [];
        foreach ($grades as $grade) {
            $numericGrade = $grade['numeric_grade'];
            
            if ($numericGrade >= 9.0) {
                $qualitative = 'Superior';
                $color = '#27ae60';
            } elseif ($numericGrade >= 7.0) {
                $qualitative = 'Alto';
                $color = '#3498db';
            } elseif ($numericGrade >= 6.0) {
                $qualitative = 'Básico';
                $color = '#f39c12';
            } else {
                $qualitative = 'Bajo';
                $color = '#e74c3c';
            }
            
            $preview[] = [
                'student_id' => $grade['student_id'],
                'numeric_grade' => $numericGrade,
                'qualitative_grade' => $qualitative,
                'color' => $color,
                'observations' => $grade['observations'] ?? ''
            ];
        }
        
        echo json_encode(['preview' => $preview]);
        
    } else {
        http_response_code(404);
        echo json_encode(['message' => 'Acción no encontrada']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Error interno del servidor']);
}
?>