<?php
require_once dirname(__DIR__) . '/config.php';

// Security headers
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
}

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['message' => 'Método no permitido']);
    exit;
}

$rawInput = file_get_contents('php://input');
if (strlen($rawInput) > 50000) { // 50KB limit for chat messages
    http_response_code(413);
    echo json_encode(['message' => 'Mensaje demasiado largo']);
    exit;
}
$input = json_decode($rawInput, true);

if (!$input || !isset($input['message']) || !isset($input['sessionId']) || !isset($input['userId'])) {
    http_response_code(400);
    echo json_encode(['message' => 'Datos incompletos']);
    exit;
}

$message = trim(filter_var($input['message'], FILTER_SANITIZE_STRING));
$sessionId = filter_var($input['sessionId'], FILTER_SANITIZE_STRING);
$userId = filter_var($input['userId'], FILTER_VALIDATE_INT);

if (!$userId || $userId <= 0) {
    http_response_code(400);
    echo json_encode(['message' => 'ID de usuario inválido']);
    exit;
}

/**
 * Detecta palabras clave críticas
 */
function checkEmergencyKeywords($input, $userId) {
    $input = strtolower($input);
    
    $emergencyKeywords = [
        'suicid' => 'high',
        'matarme' => 'high',
        'morir' => 'high',
        'no quiero vivir' => 'high',
        'acabar con todo' => 'high',
        'terminar conmigo' => 'high',
        'maltrat' => 'medium',
        'abus' => 'medium',
        'violenci' => 'medium',
        'drogas' => 'medium',
        'sobredosis' => 'high',
        'cortar' => 'medium',
        'herir' => 'medium',
        'lastimar' => 'medium',
        'bully' => 'low',
        'acos' => 'medium',
        'deprimid' => 'low',
        'ansied' => 'low',
        'pánico' => 'low',
        'miedo' => 'low',
        'solo' => 'low',
        'sola' => 'low',
        'solit' => 'low'
    ];

    $detectedKeywords = [];
    $highestSeverity = 'none';
    
    foreach ($emergencyKeywords as $keyword => $severity) {
        if (strpos($input, $keyword) !== false) {
            $detectedKeywords[] = $keyword;
            if ($severity === 'high' || ($severity === 'medium' && $highestSeverity !== 'high')) {
                $highestSeverity = $severity;
            } elseif ($severity === 'low' && $highestSeverity === 'none') {
                $highestSeverity = $severity;
            }
        }
    }
    
    if (!empty($detectedKeywords)) {
        error_log("Emergency keywords detected (User: " . intval($userId) . "): " . filter_var(implode(', ', $detectedKeywords), FILTER_SANITIZE_STRING) . " (Severity: " . filter_var($highestSeverity, FILTER_SANITIZE_STRING) . ")");

        if ($highestSeverity !== 'low') {
            sendAdminAlert($userId, 'emergency', 
                "Alerta de palabra clave detectada: " . implode(', ', $detectedKeywords), 
                $input
            );
        }
        
        return [
            'is_emergency' => true,
            'severity' => $highestSeverity,
            'keywords' => $detectedKeywords
        ];
    }
    
    return ['is_emergency' => false];
}

/**
 * Llamada a la API de Google Gemini
 */
function getAIResponse($message, $userId, $conversationHistory = []) {
    $apiKey = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '';

    if (empty($apiKey)) {
        return [
            'success' => true,
            'response' => "Hola, estoy aquí para ayudarte. Actualmente no puedo conectarme al servicio de IA. " .
                        "Por favor, intenta nuevamente en unos momentos o contacta a soporte.",
            'source' => 'fallback',
            'error' => 'API key de Gemini no configurada'
        ];
    }

    $apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=' . $apiKey;

    $systemPrompt = "Eres StudyMate, un asistente de apoyo estudiantil en Colombia. 
    - Escucha activamente y valida sentimientos
    - Ofrece apoyo emocional sin juzgar
    - Proporciona recursos de ayuda en Colombia
    - Identifica señales de crisis y ofrece contactos de emergencia locales
    - Mantén un tono cálido, empático y profesional";

    $context = $systemPrompt . "\n\nEstudiante: " . $message;

    $data = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $context]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.7,
            'topK' => 40,
            'topP' => 0.9,
            'maxOutputTokens' => 500,
        ]
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return [
            'success' => false,
            'error' => 'Error en la conexión: ' . $error,
            'source' => 'local-fallback'
        ];
    }

    $responseData = json_decode($response, true);
    $geminiText = $responseData['candidates'][0]['content']['parts'][0]['text'] 
                 ?? $responseData['candidates'][0]['output'] 
                 ?? null;

    if ($httpCode !== 200 || !$geminiText) {
        return [
            'success' => false,
            'error' => 'Respuesta inesperada de la API: ' . ($responseData['error']['message'] ?? $httpCode),
            'source' => 'local-fallback'
        ];
    }

    return [
        'success' => true,
        'response' => trim($geminiText),
        'source' => 'gemini'
    ];
}

/**
 * Respuestas locales de respaldo
 */
function getLocalResponse($input, $userId) {
    $input = strtolower($input);
    
    $emergencyCheck = checkEmergencyKeywords($input, $userId);
    if ($emergencyCheck['is_emergency']) {
        return "Detecté que podrías estar pasando por una situación difícil. No estás solo/a. 
        Si necesitas ayuda inmediata en Colombia llama al 123 o al 192 opción 4.";
    }

    if (strpos($input, 'hola') !== false) {
        return "¡Hola! Soy StudyMate, tu asistente de apoyo estudiantil. ¿En qué puedo ayudarte hoy?";
    }

    if (strpos($input, 'estresado') !== false || strpos($input, 'ansiedad') !== false) {
        return "Entiendo que te sientes estresado. Prueba la respiración profunda: inhala 4 segundos, mantén 7, exhala 8. ¿Quieres más consejos?";
    }

    return "Entiendo que dices: " . htmlspecialchars($input, ENT_QUOTES, 'UTF-8') . ". Estoy aquí para ayudarte con tus estudios y bienestar. ¿Hay algo específico en lo que pueda asistirte?";
}

/**
 * Enviar alerta a administrador
 */
function sendAdminAlert($userId, $alertType, $message, $userMessage) {
    try {
        $pdo = getDBConnection();
        $organizationId = TenantManager::getUserOrganization($userId);
        $stmt = $pdo->prepare("INSERT INTO admin_alerts (organization_id, user_id, alert_type, severity, message, user_message, status) VALUES (?, ?, ?, 'emergency', ?, ?, 'pending')");
        $stmt->execute([$organizationId, intval($userId), filter_var($alertType, FILTER_SANITIZE_STRING), filter_var($message, FILTER_SANITIZE_STRING), filter_var($userMessage, FILTER_SANITIZE_STRING)]);
    } catch (Exception $e) {
        error_log('Failed to save admin alert: ' . $e->getMessage());
    }

    $adminEmail = defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'admin@studymate.com';
    $subject = "Alerta StudyMate: $alertType";
    $body = "Se ha detectado una alerta:\n\n$message\n\nMensaje del usuario:\n$userMessage";
    @mail($adminEmail, $subject, $body);
}

// Validar tenant
require_once dirname(__DIR__) . '/utils/tenant.php';
$organizationId = TenantManager::validateTenantRequest($userId);

// Verificar límites del plan
if (!TenantManager::checkPlanLimits($organizationId, 'ai_chat')) {
    echo json_encode([
        'message' => 'El chat con IA no está disponible en tu plan actual. Contacta al administrador para actualizar.',
        'timestamp' => date('c'),
        'source' => 'plan-limitation'
    ]);
    exit;
}

// 1. Revisar si es emergencia
$emergencyCheck = checkEmergencyKeywords($message, $userId);
if ($emergencyCheck['is_emergency']) {
    error_log("EMERGENCY DETECTED - User: " . intval($userId) . " - Snippet: " . filter_var(substr($message, 0, 50), FILTER_SANITIZE_STRING));

    if (defined('ADMIN_EMAIL')) {
        $to = ADMIN_EMAIL;
        $subject = "ALERTA DE CRISIS - Usuario: $userId";
        $alertMessage = "El usuario " . intval($userId) . " ha enviado un mensaje que requiere atención inmediata:\n\n" . 
                      "Mensaje: " . filter_var($message, FILTER_SANITIZE_STRING) . "\n\n" .
                      "Hora: " . date('Y-m-d H:i:s') . "\n" .
                      "IP: " . filter_var($_SERVER['REMOTE_ADDR'] ?? 'unknown', FILTER_VALIDATE_IP) . "\n";
        $headers = "From: alertas@studymate.com" . "\r\n" .
                  "X-Mailer: PHP/" . phpversion();
        
        @mail($to, $subject, $alertMessage, $headers);
    }
    
    $emergencyResponse = [
        'success' => true,
        'response' => "🚨 **¡Hola! Veo que estás pasando por un momento muy difícil.**\n\n" .
                    "No estás solo/a y tus sentimientos son importantes.\n\n" .
                    "**En Colombia puedes contactar a:**\n" .
                    "• Línea de atención psicológica: 192 opción 4\n" .
                    "• Emergencias: 123\n" .
                    "• Línea de la vida: 01 8000 113 113\n" .
                    "• ICBF Línea 141 para menores\n" .
                    "• Línea púrpura para mujeres: 155\n\n" .
                    "Por favor busca apoyo profesional o contacta a alguien de confianza.",
        'is_emergency' => true,
        'country' => 'CO',
        'timestamp' => date('c')
    ];
    
    echo json_encode($emergencyResponse);
    exit;
}

// 2. Intentar con Gemini
$aiResponse = getAIResponse($message, $userId);

// 3. Si éxito, usar respuesta de Gemini
if (isset($aiResponse['success']) && $aiResponse['success']) {
    echo json_encode([
        'message' => $aiResponse['response'],
        'timestamp' => date('c'),
        'source' => $aiResponse['source']
    ]);
    exit;
}

// 4. Si falla, usar fallback local
$responseMessage = getLocalResponse($message, $userId);

echo json_encode([
    'message' => $responseMessage,
    'timestamp' => date('c'),
    'source' => 'local-fallback',
    'api_error' => $aiResponse['error'] ?? null
]);
?>
