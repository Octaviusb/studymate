<?php
require_once 'config.php';
header('Content-Type: application/json');
session_start();

$method = $_SERVER['REQUEST_METHOD'];
if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['message' => 'Método no permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? 'init';

if ($action === 'init') {
    // Generar código 2FA de 6 dígitos
    $code = str_pad(strval(random_int(0, 999999)), 6, '0', STR_PAD_LEFT);
    $_SESSION['otp_code'] = $code;
    $_SESSION['otp_expires'] = time() + 300; // 5 minutos
    // En una implementación real, enviar por email/SMS.
    echo json_encode(['message' => 'Código enviado', 'demo_code' => $code]);
    exit;
}

if ($action === 'verify') {
    $code = trim($input['code'] ?? '');
    if (!isset($_SESSION['otp_code']) || !isset($_SESSION['otp_expires'])) {
        http_response_code(400);
        echo json_encode(['message' => 'No hay código activo']);
        exit;
    }
    if (time() > $_SESSION['otp_expires']) {
        unset($_SESSION['otp_code'], $_SESSION['otp_expires']);
        http_response_code(400);
        echo json_encode(['message' => 'Código expirado']);
        exit;
    }
    if ($code !== $_SESSION['otp_code']) {
        http_response_code(401);
        echo json_encode(['message' => 'Código incorrecto']);
        exit;
    }
    unset($_SESSION['otp_code'], $_SESSION['otp_expires']);
    echo json_encode(['message' => 'Verificado']);
    exit;
}

http_response_code(400);
echo json_encode(['message' => 'Acción inválida']);
