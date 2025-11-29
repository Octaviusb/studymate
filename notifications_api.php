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
    
    // Verificar usuario
    $stmt = $pdo->prepare("SELECT role, organization_id FROM users WHERE id = ? AND status = 'active'");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        http_response_code(403);
        echo json_encode(['message' => 'Usuario no encontrado']);
        exit;
    }
    
    if ($action === 'get_notifications') {
        $notifications = [];
        
        // Notificaciones según el rol
        switch($user['role']) {
            case 'teacher':
                $notifications = [
                    ['id' => 1, 'type' => 'grade_reminder', 'message' => 'Tienes 5 calificaciones pendientes por ingresar', 'time' => '2 min', 'priority' => 'high'],
                    ['id' => 2, 'type' => 'meeting', 'message' => 'Reunión de docentes mañana a las 3:00 PM', 'time' => '1 hora', 'priority' => 'medium'],
                    ['id' => 3, 'type' => 'student_alert', 'message' => 'Ana García necesita refuerzo en Química', 'time' => '3 horas', 'priority' => 'high']
                ];
                break;
                
            case 'student':
                $notifications = [
                    ['id' => 1, 'type' => 'new_grade', 'message' => 'Nueva calificación en Matemáticas: 8.5', 'time' => '10 min', 'priority' => 'medium'],
                    ['id' => 2, 'type' => 'assignment', 'message' => 'Tarea de Historia vence mañana', 'time' => '2 horas', 'priority' => 'high'],
                    ['id' => 3, 'type' => 'event', 'message' => 'Reunión de padres el 15 de diciembre', 'time' => '1 día', 'priority' => 'low']
                ];
                break;
                
            case 'parent':
                $notifications = [
                    ['id' => 1, 'type' => 'grade_alert', 'message' => 'Ana tiene una calificación baja en Química (6.2)', 'time' => '30 min', 'priority' => 'high'],
                    ['id' => 2, 'type' => 'payment', 'message' => 'Pago de pensión vence en 3 días', 'time' => '2 horas', 'priority' => 'high'],
                    ['id' => 3, 'type' => 'message', 'message' => 'Mensaje del Prof. García sobre Ana', 'time' => '4 horas', 'priority' => 'medium']
                ];
                break;
                
            case 'admin':
            case 'coordinator':
                $notifications = [
                    ['id' => 1, 'type' => 'payment_overdue', 'message' => '3 estudiantes con pagos vencidos', 'time' => '15 min', 'priority' => 'high'],
                    ['id' => 2, 'type' => 'performance', 'message' => 'Rendimiento en Química por debajo del 70%', 'time' => '1 hora', 'priority' => 'medium'],
                    ['id' => 3, 'type' => 'system', 'message' => 'Backup del sistema completado', 'time' => '3 horas', 'priority' => 'low']
                ];
                break;
                
            default:
                $notifications = [];
        }
        
        echo json_encode(['notifications' => $notifications]);
        
    } elseif ($action === 'mark_read') {
        $notificationId = $_POST['notification_id'] ?? null;
        
        if ($notificationId) {
            // Simular marcar como leída
            echo json_encode(['message' => 'Notificación marcada como leída']);
        } else {
            http_response_code(400);
            echo json_encode(['message' => 'ID de notificación requerido']);
        }
        
    } elseif ($action === 'send_notification') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Simular envío de notificación
        echo json_encode(['message' => 'Notificación enviada exitosamente']);
        
    } else {
        http_response_code(404);
        echo json_encode(['message' => 'Acción no encontrada']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Error interno del servidor']);
}
?>