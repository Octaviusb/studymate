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
    $stmt = $pdo->prepare("SELECT role, organization_id, full_name FROM users WHERE id = ? AND status = 'active'");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        http_response_code(403);
        echo json_encode(['message' => 'Usuario no encontrado']);
        exit;
    }
    
    if ($action === 'get_contacts') {
        // Obtener contactos según el rol
        $contacts = [];
        
        switch($user['role']) {
            case 'teacher':
                // Docentes pueden contactar: estudiantes, padres, coordinadores, admin
                $stmt = $pdo->prepare("
                    SELECT id, full_name, email, role 
                    FROM users 
                    WHERE organization_id = ? 
                    AND role IN ('student', 'parent', 'coordinator', 'admin', 'secretary') 
                    AND status = 'active'
                    ORDER BY role, full_name
                ");
                $stmt->execute([$user['organization_id']]);
                $contacts = $stmt->fetchAll();
                break;
                
            case 'student':
                // Estudiantes pueden contactar: docentes, coordinadores
                $stmt = $pdo->prepare("
                    SELECT id, full_name, email, role 
                    FROM users 
                    WHERE organization_id = ? 
                    AND role IN ('teacher', 'coordinator', 'secretary') 
                    AND status = 'active'
                    ORDER BY role, full_name
                ");
                $stmt->execute([$user['organization_id']]);
                $contacts = $stmt->fetchAll();
                break;
                
            case 'parent':
                // Padres pueden contactar: docentes, coordinadores, admin
                $stmt = $pdo->prepare("
                    SELECT id, full_name, email, role 
                    FROM users 
                    WHERE organization_id = ? 
                    AND role IN ('teacher', 'coordinator', 'admin', 'secretary') 
                    AND status = 'active'
                    ORDER BY role, full_name
                ");
                $stmt->execute([$user['organization_id']]);
                $contacts = $stmt->fetchAll();
                break;
                
            case 'admin':
            case 'coordinator':
            case 'secretary':
                // Admin/Coordinadores pueden contactar a todos
                $stmt = $pdo->prepare("
                    SELECT id, full_name, email, role 
                    FROM users 
                    WHERE organization_id = ? 
                    AND id != ? 
                    AND status = 'active'
                    ORDER BY role, full_name
                ");
                $stmt->execute([$user['organization_id'], $userId]);
                $contacts = $stmt->fetchAll();
                break;
        }
        
        echo json_encode(['contacts' => $contacts]);
        
    } elseif ($action === 'get_messages') {
        // Obtener mensajes del usuario
        $messages = [
            [
                'id' => 1,
                'from_name' => 'Prof. García',
                'from_role' => 'teacher',
                'subject' => 'Progreso en Matemáticas',
                'message' => 'Ana ha mostrado excelente progreso en álgebra. Felicitaciones por el apoyo en casa.',
                'sent_at' => '2024-12-10 14:30:00',
                'read' => false
            ],
            [
                'id' => 2,
                'from_name' => 'Coordinación Académica',
                'from_role' => 'coordinator',
                'subject' => 'Reunión de Padres',
                'message' => 'Recordatorio: Reunión de padres el 15 de diciembre a las 6:00 PM.',
                'sent_at' => '2024-12-08 10:00:00',
                'read' => true
            ]
        ];
        
        echo json_encode(['messages' => $messages]);
        
    } elseif ($action === 'send_message') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $recipientId = $input['recipient_id'] ?? null;
        $subject = $input['subject'] ?? '';
        $message = $input['message'] ?? '';
        
        if (!$recipientId || !$subject || !$message) {
            http_response_code(400);
            echo json_encode(['message' => 'Todos los campos son requeridos']);
            exit;
        }
        
        // Verificar que el destinatario existe y está en la misma organización
        $stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE id = ? AND organization_id = ? AND status = 'active'");
        $stmt->execute([$recipientId, $user['organization_id']]);
        $recipient = $stmt->fetch();
        
        if (!$recipient) {
            http_response_code(404);
            echo json_encode(['message' => 'Destinatario no encontrado']);
            exit;
        }
        
        // Simular envío de mensaje (en producción, guardar en base de datos)
        echo json_encode([
            'message' => 'Mensaje enviado exitosamente',
            'recipient' => $recipient['full_name']
        ]);
        
    } elseif ($action === 'get_announcements') {
        // Obtener anuncios institucionales
        $announcements = [
            [
                'id' => 1,
                'title' => 'Reunión de Padres',
                'content' => 'Se convoca a reunión general de padres de familia el 15 de diciembre a las 6:00 PM.',
                'priority' => 'high',
                'created_at' => '2024-12-08 09:00:00',
                'author' => 'Coordinación Académica'
            ],
            [
                'id' => 2,
                'title' => 'Vacaciones de Fin de Año',
                'content' => 'Las clases se suspenden del 20 de diciembre al 15 de enero.',
                'priority' => 'medium',
                'created_at' => '2024-12-05 15:30:00',
                'author' => 'Rectoría'
            ]
        ];
        
        echo json_encode(['announcements' => $announcements]);
        
    } elseif ($action === 'create_announcement') {
        // Solo admin/coordinadores pueden crear anuncios
        if (!in_array($user['role'], ['admin', 'coordinator', 'super_admin'])) {
            http_response_code(403);
            echo json_encode(['message' => 'No tienes permisos para crear anuncios']);
            exit;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $title = $input['title'] ?? '';
        $content = $input['content'] ?? '';
        $priority = $input['priority'] ?? 'medium';
        
        if (!$title || !$content) {
            http_response_code(400);
            echo json_encode(['message' => 'Título y contenido son requeridos']);
            exit;
        }
        
        // Simular creación de anuncio
        echo json_encode(['message' => 'Anuncio creado exitosamente']);
        
    } elseif ($action === 'schedule_meeting') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $participantId = $input['participant_id'] ?? null;
        $date = $input['date'] ?? '';
        $time = $input['time'] ?? '';
        $subject = $input['subject'] ?? '';
        
        if (!$participantId || !$date || !$time || !$subject) {
            http_response_code(400);
            echo json_encode(['message' => 'Todos los campos son requeridos']);
            exit;
        }
        
        // Simular programación de reunión
        echo json_encode(['message' => 'Reunión programada exitosamente']);
        
    } else {
        http_response_code(404);
        echo json_encode(['message' => 'Acción no encontrada']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Error interno del servidor']);
}
?>