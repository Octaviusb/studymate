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
    
    // Verificar permisos (admin, coordinator, secretary)
    $stmt = $pdo->prepare("SELECT role, organization_id FROM users WHERE id = ? AND role IN ('super_admin', 'admin', 'coordinator', 'secretary')");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        http_response_code(403);
        echo json_encode(['message' => 'Acceso denegado']);
        exit;
    }
    
    if ($action === 'overdue_students') {
        // Datos demo de estudiantes en mora
        $overdueStudents = [
            [
                'id' => 2,
                'full_name' => 'Carlos López',
                'email' => 'carlos.lopez@demo.com',
                'overdue_invoices' => 2,
                'total_debt' => 450000,
                'oldest_due_date' => '2024-10-15',
                'organization_name' => 'Demo School'
            ]
        ];
        
        echo json_encode(['overdue_students' => $overdueStudents]);
        
    } elseif ($action === 'send_notification') {
        // Enviar notificación de morosidad (Art. 22 - 15 días previos)
        $input = json_decode(file_get_contents('php://input'), true);
        $studentId = $input['student_id'];
        $invoiceId = $input['invoice_id'];
        $notificationType = $input['notification_type'];
        
        // Verificar que la factura existe y está vencida
        $stmt = $pdo->prepare("SELECT * FROM student_invoices WHERE id = ? AND status = 'overdue'");
        $stmt->execute([$invoiceId]);
        $invoice = $stmt->fetch();
        
        if (!$invoice) {
            http_response_code(404);
            echo json_encode(['message' => 'Factura no encontrada o no está vencida']);
            exit;
        }
        
        // Crear notificación con 15 días de debido proceso
        $notificationDate = date('Y-m-d');
        $dueProcessDate = date('Y-m-d', strtotime('+15 days'));
        
        $stmt = $pdo->prepare("
            INSERT INTO morosity_notifications 
            (organization_id, student_id, invoice_id, notification_type, notification_date, due_process_date, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $notes = "Notificación enviada conforme al Art. 22 del Reglamento sobre Centros Docentes Privados. Se otorgan 15 días para regularizar la situación.";
        
        $stmt->execute([
            $invoice['organization_id'],
            $studentId,
            $invoiceId,
            $notificationType,
            $notificationDate,
            $dueProcessDate,
            $notes
        ]);
        
        echo json_encode(['message' => 'Notificación enviada exitosamente', 'due_process_date' => $dueProcessDate]);
        
    } elseif ($action === 'create_payment_agreement') {
        // Crear acuerdo de pago (protege derecho a la educación)
        $input = json_decode(file_get_contents('php://input'), true);
        
        $stmt = $pdo->prepare("
            INSERT INTO payment_agreements 
            (organization_id, student_id, total_debt, monthly_payment, start_date, end_date, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $input['organization_id'],
            $input['student_id'],
            $input['total_debt'],
            $input['monthly_payment'],
            $input['start_date'],
            $input['end_date'],
            'Acuerdo de pago creado para proteger el derecho fundamental a la educación del estudiante.'
        ]);
        
        echo json_encode(['message' => 'Acuerdo de pago creado exitosamente']);
        
    } elseif ($action === 'suspend_services') {
        // Suspender servicios SOLO al final del período (cumple normativa)
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Verificar que estamos al final de un período académico
        $stmt = $pdo->prepare("
            SELECT * FROM academic_periods 
            WHERE id = ? AND end_date <= CURDATE() AND status = 'closed'
        ");
        $stmt->execute([$input['period_id']]);
        $period = $stmt->fetch();
        
        if (!$period) {
            http_response_code(400);
            echo json_encode(['message' => 'Solo se puede suspender servicios al finalizar un período académico cerrado']);
            exit;
        }
        
        // Verificar que se cumplió el debido proceso (15 días)
        $stmt = $pdo->prepare("
            SELECT * FROM morosity_notifications 
            WHERE student_id = ? AND due_process_date <= CURDATE() AND status = 'sent'
        ");
        $stmt->execute([$input['student_id']]);
        $notification = $stmt->fetch();
        
        if (!$notification) {
            http_response_code(400);
            echo json_encode(['message' => 'Debe cumplirse el debido proceso de 15 días antes de la suspensión']);
            exit;
        }
        
        // Crear suspensión
        $stmt = $pdo->prepare("
            INSERT INTO service_suspensions 
            (organization_id, student_id, period_id, suspension_type, reason, suspension_date, legal_compliance_notes)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $legalNotes = "Suspensión aplicada conforme a la normativa: 1) Notificación previa de 15 días cumplida. 2) Suspensión al finalizar período académico. 3) Se respeta el derecho de defensa del estudiante.";
        
        $stmt->execute([
            $input['organization_id'],
            $input['student_id'],
            $input['period_id'],
            $input['suspension_type'],
            $input['reason'],
            date('Y-m-d'),
            $legalNotes
        ]);
        
        echo json_encode(['message' => 'Suspensión aplicada conforme a la normativa legal']);
        
    } elseif ($action === 'legal_report') {
        // Reporte de cumplimiento legal
        $sql = "SELECT 
                    COUNT(CASE WHEN mn.notification_type = 'first_notice' THEN 1 END) as first_notices,
                    COUNT(CASE WHEN mn.notification_type = 'final_notice' THEN 1 END) as final_notices,
                    COUNT(CASE WHEN ss.status = 'active' THEN 1 END) as active_suspensions,
                    COUNT(CASE WHEN pa.status = 'active' THEN 1 END) as active_agreements
                FROM morosity_notifications mn
                LEFT JOIN service_suspensions ss ON mn.student_id = ss.student_id
                LEFT JOIN payment_agreements pa ON mn.student_id = pa.student_id";
        
        if ($user['role'] !== 'super_admin') {
            $sql .= " WHERE mn.organization_id = ?";
            $params = [$user['organization_id']];
        } else {
            $params = [];
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $report = $stmt->fetch();
        
        echo json_encode(['legal_report' => $report]);
        
    } else {
        http_response_code(404);
        echo json_encode(['message' => 'Acción no encontrada']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Error interno del servidor']);
}
?>