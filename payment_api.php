<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Configuración de la base de datos
require_once 'config.php';

// Modo demo para desarrollo - Activado para simular pagos completados exitosamente
define('DEMO_MODE', true);

// Función para enviar email de confirmación de pago
function sendPaymentConfirmationEmail($paymentData) {
    $to = $paymentData['email'];
    $subject = "Confirmación de Pago - StudyMate SaaS";

    $message = "
    <html>
    <head>
        <title>Confirmación de Pago</title>
        <style>
            body { font-family: Arial, sans-serif; }
            .header { background: #3498db; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; }
            .payment-details { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0; }
            .footer { background: #ecf0f1; padding: 15px; text-align: center; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='header'>
            <h1>StudyMate SaaS</h1>
            <h2>Confirmación de Pago</h2>
        </div>

        <div class='content'>
            <p>Hola <strong>{$paymentData['student_name']}</strong>,</p>

            <p>Tu pago ha sido procesado exitosamente.</p>

            <div class='payment-details'>
                <h3>Detalles del Pago:</h3>
                <p><strong>Referencia:</strong> {$paymentData['reference']}</p>
                <p><strong>Monto:</strong> $" . number_format($paymentData['amount'], 0, ',', '.') . "</p>
                <p><strong>Método:</strong> {$paymentData['payment_method']}</p>
                <p><strong>Fecha:</strong> {$paymentData['payment_date']}</p>
                <p><strong>Estado:</strong> {$paymentData['status']}</p>
                <p><strong>Concepto:</strong> {$paymentData['description']}</p>
            </div>

            <p>Si tienes alguna pregunta, no dudes en contactarnos.</p>

            <p>¡Gracias por tu pago!</p>
        </div>

        <div class='footer'>
            <p>StudyMate SaaS - Sistema Educativo</p>
            <p>Este es un email automático, por favor no responder.</p>
        </div>
    </body>
    </html>
    ";

    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: StudyMate SaaS <noreply@studymatesaas.com>" . "\r\n";

    // En modo demo, solo simulamos el envío
    if (defined('DEMO_MODE') && DEMO_MODE) {
        error_log("DEMO: Email de confirmación enviado a {$to} - Referencia: {$paymentData['reference']}");
        return true;
    }

    return mail($to, $subject, $message, $headers);
}

// Función para procesar pago PSE
function processPSEPayment($paymentData) {
    // Simular proceso completo de PSE - para demo
    $reference = 'PSE' . time() . rand(100, 999);

    // Simular pago completado exitosamente
    return [
        'success' => true,
        'status' => 'completed', // Completado para demo
        'reference' => $reference,
        'bank_url' => 'https://www.pse.com.co/persona', // URL real de PSE
        'message' => 'Pago procesado exitosamente vía PSE'
    ];
}

// Función para procesar pago Nequi
function processNequiPayment($paymentData) {
    // Simular proceso completo de Nequi - para demo
    $reference = 'NEQUI' . time() . rand(100, 999);

    // Simular pago aprobado exitosamente
    return [
        'success' => true,
        'status' => 'completed', // Completado para demo
        'reference' => $reference,
        'phone_verification' => true,
        'message' => 'Pago aprobado exitosamente en Nequi'
    ];
}

// Función para procesar pago Daviplata
function processDaviplataPayment($paymentData) {
    // Simular proceso completo de Daviplata - para demo
    $reference = 'DAVIPLATA' . time() . rand(100, 999);

    // Simular pago confirmado exitosamente
    return [
        'success' => true,
        'status' => 'completed', // Completado para demo
        'reference' => $reference,
        'qr_code' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAA...', // QR code simulado
        'message' => 'Pago confirmado exitosamente en Daviplata'
    ];
}

// Función para procesar pago por transferencia
function processTransferPayment($paymentData) {
    // Simular proceso completo de transferencia - para demo
    $reference = 'TRANS' . time() . rand(100, 999);

    return [
        'success' => true,
        'status' => 'completed', // Completado para demo
        'reference' => $reference,
        'bank_details' => [
            'bank_name' => 'Bancolombia',
            'account_number' => '123-456789-0',
            'account_holder' => 'StudyMate SaaS',
            'nit' => '901.234.567-8'
        ],
        'message' => 'Transferencia procesada exitosamente'
    ];
}

try {
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'create_payment':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }

            $data = json_decode(file_get_contents('php://input'), true);

            if (!$data) {
                throw new Exception('Datos inválidos');
            }

            // Validar datos requeridos
            $required = ['student_id', 'amount', 'payment_method', 'description'];
            foreach ($required as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    throw new Exception("Campo requerido faltante: {$field}");
                }
            }

            // Obtener información del estudiante
            $stmt = $pdo->prepare("SELECT full_name, email FROM users WHERE id = ? AND role = 'student'");
            $stmt->execute([$data['student_id']]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$student) {
                throw new Exception('Estudiante no encontrado');
            }

            // Procesar pago según método
            $paymentResult = null;
            switch ($data['payment_method']) {
                case 'pse':
                    $paymentResult = processPSEPayment($data);
                    break;
                case 'nequi':
                    $paymentResult = processNequiPayment($data);
                    break;
                case 'daviplata':
                    $paymentResult = processDaviplataPayment($data);
                    break;
                case 'transfer':
                    $paymentResult = processTransferPayment($data);
                    break;
                default:
                    throw new Exception('Método de pago no válido');
            }

            if (!$paymentResult['success']) {
                throw new Exception($paymentResult['message']);
            }

            // Guardar pago en base de datos
            $stmt = $pdo->prepare("
                INSERT INTO payments (
                    student_id, amount, payment_method, description,
                    reference, status, payment_data, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");

            $stmt->execute([
                $data['student_id'],
                $data['amount'],
                $data['payment_method'],
                $data['description'],
                $paymentResult['reference'],
                $paymentResult['status'],
                json_encode($paymentResult)
            ]);

            $paymentId = $pdo->lastInsertId();

            // Preparar datos para email
            $emailData = [
                'email' => $student['email'],
                'student_name' => $student['full_name'],
                'reference' => $paymentResult['reference'],
                'amount' => $data['amount'],
                'payment_method' => strtoupper($data['payment_method']),
                'payment_date' => date('d/m/Y H:i'),
                'status' => $paymentResult['status'] === 'completed' ? 'Completado' : 'En Proceso',
                'description' => $data['description']
            ];

            // Enviar email de confirmación
            sendPaymentConfirmationEmail($emailData);

            echo json_encode([
                'success' => true,
                'payment_id' => $paymentId,
                'reference' => $paymentResult['reference'],
                'status' => $paymentResult['status'],
                'message' => $paymentResult['message'],
                'payment_data' => $paymentResult
            ]);

            break;

        case 'get_payment_history':
            $studentId = $_GET['student_id'] ?? null;

            if (!$studentId) {
                throw new Exception('ID de estudiante requerido');
            }

            $stmt = $pdo->prepare("
                SELECT id, amount, payment_method, description, reference, status,
                       payment_data, created_at
                FROM payments
                WHERE student_id = ?
                ORDER BY created_at DESC
            ");

            $stmt->execute([$studentId]);
            $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Formatear datos para respuesta
            foreach ($payments as &$payment) {
                $payment['payment_data'] = json_decode($payment['payment_data'], true);
                $payment['formatted_amount'] = '$' . number_format($payment['amount'], 0, ',', '.');
                $payment['formatted_date'] = date('d/m/Y H:i', strtotime($payment['created_at']));
            }

            echo json_encode([
                'success' => true,
                'payments' => $payments
            ]);

            break;

        case 'check_payment_status':
            $paymentId = $_GET['payment_id'] ?? null;

            if (!$paymentId) {
                throw new Exception('ID de pago requerido');
            }

            $stmt = $pdo->prepare("SELECT * FROM payments WHERE id = ?");
            $stmt->execute([$paymentId]);
            $payment = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$payment) {
                throw new Exception('Pago no encontrado');
            }

            echo json_encode([
                'success' => true,
                'payment' => $payment
            ]);

            break;

        default:
            throw new Exception('Acción no válida');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>