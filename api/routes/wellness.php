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
$path = str_replace('/wellness', '', $_SERVER['REQUEST_URI']);
$path = explode('?', $path)[0];

if ($method === 'GET' && $path === '') {
    getWellnessEntries();
} elseif ($method === 'POST' && $path === '') {
    createWellnessEntry();
} else {
    http_response_code(404);
    echo json_encode(['message' => 'Endpoint no encontrado']);
}

function getWellnessEntries() {
    $userId = filter_var($_GET['user_id'] ?? null, FILTER_VALIDATE_INT);

    if (!$userId || $userId <= 0) {
        http_response_code(400);
        echo json_encode(['message' => 'user_id válido requerido']);
        return;
    }
    
    require_once dirname(__DIR__) . '/utils/tenant.php';
    $organizationId = TenantManager::validateTenantRequest($userId);

    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM wellness_entries WHERE user_id = ? AND organization_id = ? ORDER BY date DESC");
        $stmt->execute([$userId, $organizationId]);
        $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calculate stats (example)
        $totalEntries = count($entries);
        $averageMood = $totalEntries > 0 ? array_sum(array_column($entries, 'mood_value')) / $totalEntries : 0;

        echo json_encode([
            'entries' => $entries,
            'stats' => [
                'average_mood' => round($averageMood, 2),
                'total_entries' => $totalEntries,
                'exercises_completed' => 0 // Placeholder
            ]
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Error interno del servidor']);
    }
}

function createWellnessEntry() {
    $rawInput = file_get_contents('php://input');
    if (strlen($rawInput) > 5120) {
        http_response_code(413);
        echo json_encode(['message' => 'Datos demasiado grandes']);
        return;
    }
    
    $input = json_decode($rawInput, true);

    if (!$input || !isset($input['mood']) || !isset($input['user_id'])) {
        http_response_code(400);
        echo json_encode(['message' => 'Mood y user_id requeridos']);
        return;
    }

    $mood = trim(filter_var($input['mood'], FILTER_SANITIZE_STRING));
    $userId = filter_var($input['user_id'], FILTER_VALIDATE_INT);
    $notes = filter_var($input['notes'] ?? '', FILTER_SANITIZE_STRING);
    
    if (!$userId || $userId <= 0) {
        http_response_code(400);
        echo json_encode(['message' => 'user_id válido requerido']);
        return;
    }
    
    require_once dirname(__DIR__) . '/utils/tenant.php';
    $organizationId = TenantManager::validateTenantRequest($userId);
    $moodValues = ['excellent' => 5, 'good' => 4, 'okay' => 3, 'bad' => 2, 'terrible' => 1];
    $moodLower = strtolower($mood);
    if (!array_key_exists($moodLower, $moodValues)) {
        http_response_code(400);
        echo json_encode(['message' => 'Mood inválido']);
        return;
    }
    $moodValue = $moodValues[$moodLower];
    $date = date('Y-m-d');

    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("INSERT INTO wellness_entries (organization_id, user_id, mood, mood_value, notes, date) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$organizationId, $userId, $mood, $moodValue, $notes, $date]);

        $entryId = $pdo->lastInsertId();

        echo json_encode([
            'id' => $entryId,
            'user_id' => $userId,
            'mood' => $mood,
            'mood_value' => $moodValue,
            'notes' => $notes,
            'date' => $date,
            'created_at' => date('Y-m-d H:i:s')
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Error interno del servidor']);
    }
}
?>
