<?php
require_once 'config.php';

header('Content-Type: application/json');

try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT id, name, domain, plan FROM organizations WHERE status = 'active' ORDER BY name");
    $stmt->execute();
    $organizations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['organizations' => $organizations]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Error interno del servidor']);
}
?>