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

// Modo demo para desarrollo
define('DEMO_MODE', true);

// Función para enviar reporte de pagos a admin y secretaria
function sendPaymentReport($reportData) {
    $adminEmails = ['admin@studymatesaas.com', 'secretaria@studymatesaas.com'];

    $subject = "Reporte de Pagos - StudyMate SaaS - " . date('d/m/Y');

    $message = "
    <html>
    <head>
        <title>Reporte de Pagos Diarios</title>
        <style>
            body { font-family: Arial, sans-serif; }
            .header { background: #3498db; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; }
            .summary { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0; }
            .payment-item { border: 1px solid #ddd; padding: 10px; margin: 5px 0; border-radius: 3px; }
            .payment-completed { border-left: 4px solid #27ae60; }
            .payment-pending { border-left: 4px solid #f39c12; }
            .footer { background: #ecf0f1; padding: 15px; text-align: center; font-size: 12px; }
            .stats { display: flex; justify-content: space-around; margin: 20px 0; }
            .stat-box { text-align: center; padding: 10px; background: white; border-radius: 5px; }
        </style>
    </head>
    <body>
        <div class='header'>
            <h1>StudyMate SaaS</h1>
            <h2>Reporte de Pagos - " . date('d/m/Y') . "</h2>
        </div>

        <div class='content'>
            <div class='summary'>
                <h3>📊 Resumen del Día</h3>
                <div class='stats'>
                    <div class='stat-box'>
                        <strong>Total Pagos:</strong><br>
                        <span style='font-size: 24px; color: #3498db;'>{$reportData['total_payments']}</span>
                    </div>
                    <div class='stat-box'>
                        <strong>Monto Total:</strong><br>
                        <span style='font-size: 24px; color: #27ae60;'>$" . number_format($reportData['total_amount'], 0, ',', '.') . "</span>
                    </div>
                    <div class='stat-box'>
                        <strong>Pagos Completados:</strong><br>
                        <span style='font-size: 24px; color: #27ae60;'>{$reportData['completed_payments']}</span>
                    </div>
                    <div class='stat-box'>
                        <strong>Pagos Pendientes:</strong><br>
                        <span style='font-size: 24px; color: #f39c12;'>{$reportData['pending_payments']}</span>
                    </div>
                </div>
            </div>

            <h3>💳 Detalle de Pagos</h3>
    ";

    foreach ($reportData['payments'] as $payment) {
        $statusClass = $payment['status'] === 'completed' ? 'payment-completed' : 'payment-pending';
        $statusText = $payment['status'] === 'completed' ? 'Completado' : 'Pendiente';

        $message .= "
            <div class='payment-item {$statusClass}'>
                <strong>{$payment['student_name']}</strong> -
                <span style='color: #3498db;'>{$payment['payment_method']}</span> -
                <span style='color: #27ae60;'>$" . number_format($payment['amount'], 0, ',', '.') . "</span> -
                {$payment['description']} -
                <strong>{$statusText}</strong>
                <br><small>Referencia: {$payment['reference']} | Fecha: {$payment['formatted_date']}</small>
            </div>
        ";
    }

    $message .= "
            <h3>📈 Estadísticas por Método de Pago</h3>
            <ul>
    ";

    foreach ($reportData['method_stats'] as $method => $count) {
        $message .= "<li><strong>" . strtoupper($method) . ":</strong> {$count} pagos</li>";
    }

    $message .= "
            </ul>
        </div>

        <div class='footer'>
            <p>StudyMate SaaS - Sistema de Reportes Automáticos</p>
            <p>Este reporte se genera diariamente a las 6:00 PM</p>
        </div>
    </body>
    </html>
    ";

    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: StudyMate SaaS Reportes <reportes@studymatesaas.com>" . "\r\n";

    // En modo demo, solo simulamos el envío
    if (defined('DEMO_MODE') && DEMO_MODE) {
        error_log("DEMO: Reporte de pagos enviado a " . implode(', ', $adminEmails));
        return true;
    }

    $success = true;
    foreach ($adminEmails as $email) {
        if (!mail($email, $subject, $message, $headers)) {
            $success = false;
        }
    }

    return $success;
}

try {
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'generate_daily_report':
            // Obtener pagos del día actual
            $stmt = $pdo->prepare("
                SELECT
                    p.*,
                    u.full_name as student_name,
                    DATE_FORMAT(p.created_at, '%d/%m/%Y %H:%i') as formatted_date
                FROM payments p
                JOIN users u ON p.student_id = u.id
                WHERE DATE(p.created_at) = CURDATE()
                ORDER BY p.created_at DESC
            ");

            $stmt->execute();
            $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Calcular estadísticas
            $totalPayments = count($payments);
            $totalAmount = array_sum(array_column($payments, 'amount'));
            $completedPayments = count(array_filter($payments, fn($p) => $p['status'] === 'completed'));
            $pendingPayments = $totalPayments - $completedPayments;

            // Estadísticas por método
            $methodStats = [];
            foreach ($payments as $payment) {
                $method = $payment['payment_method'];
                $methodStats[$method] = ($methodStats[$method] ?? 0) + 1;
            }

            $reportData = [
                'total_payments' => $totalPayments,
                'total_amount' => $totalAmount,
                'completed_payments' => $completedPayments,
                'pending_payments' => $pendingPayments,
                'payments' => $payments,
                'method_stats' => $methodStats,
                'generated_at' => date('Y-m-d H:i:s')
            ];

            // Enviar reporte por email
            $emailSent = sendPaymentReport($reportData);

            echo json_encode([
                'success' => true,
                'report' => $reportData,
                'email_sent' => $emailSent,
                'message' => 'Reporte diario generado y enviado exitosamente'
            ]);

            break;

        case 'get_payment_stats':
            $period = $_GET['period'] ?? 'today'; // today, week, month

            $dateCondition = match($period) {
                'today' => 'DATE(p.created_at) = CURDATE()',
                'week' => 'YEARWEEK(p.created_at) = YEARWEEK(CURDATE())',
                'month' => 'MONTH(p.created_at) = MONTH(CURDATE()) AND YEAR(p.created_at) = YEAR(CURDATE())',
                default => 'DATE(p.created_at) = CURDATE()'
            };

            // Estadísticas generales
            $stmt = $pdo->prepare("
                SELECT
                    COUNT(*) as total_payments,
                    SUM(amount) as total_amount,
                    AVG(amount) as avg_amount,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_payments,
                    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_payments
                FROM payments p
                WHERE {$dateCondition}
            ");

            $stmt->execute();
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);

            // Estadísticas por método de pago
            $stmt = $pdo->prepare("
                SELECT
                    payment_method,
                    COUNT(*) as count,
                    SUM(amount) as total_amount
                FROM payments p
                WHERE {$dateCondition}
                GROUP BY payment_method
                ORDER BY count DESC
            ");

            $stmt->execute();
            $methodStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Pagos recientes
            $stmt = $pdo->prepare("
                SELECT
                    p.*,
                    u.full_name as student_name,
                    DATE_FORMAT(p.created_at, '%d/%m/%Y %H:%i') as formatted_date
                FROM payments p
                JOIN users u ON p.student_id = u.id
                WHERE {$dateCondition}
                ORDER BY p.created_at DESC
                LIMIT 10
            ");

            $stmt->execute();
            $recentPayments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'stats' => $stats,
                'method_stats' => $methodStats,
                'recent_payments' => $recentPayments,
                'period' => $period
            ]);

            break;

        case 'export_payments_csv':
            $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
            $endDate = $_GET['end_date'] ?? date('Y-m-d');

            $stmt = $pdo->prepare("
                SELECT
                    p.id,
                    u.full_name as student_name,
                    u.email as student_email,
                    p.amount,
                    p.payment_method,
                    p.description,
                    p.reference,
                    p.status,
                    DATE_FORMAT(p.created_at, '%Y-%m-%d %H:%i:%s') as created_at
                FROM payments p
                JOIN users u ON p.student_id = u.id
                WHERE DATE(p.created_at) BETWEEN ? AND ?
                ORDER BY p.created_at DESC
            ");

            $stmt->execute([$startDate, $endDate]);
            $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Generar CSV
            $csv = "ID,Estudiante,Email,Monto,Método Pago,Descripción,Referencia,Estado,Fecha\n";

            foreach ($payments as $payment) {
                $csv .= implode(',', [
                    $payment['id'],
                    '"' . $payment['student_name'] . '"',
                    $payment['student_email'],
                    $payment['amount'],
                    $payment['payment_method'],
                    '"' . $payment['description'] . '"',
                    $payment['reference'],
                    $payment['status'],
                    $payment['created_at']
                ]) . "\n";
            }

            // Enviar archivo CSV
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="pagos_' . $startDate . '_a_' . $endDate . '.csv"');
            echo $csv;
            exit;

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