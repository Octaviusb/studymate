<?php
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$action = $_GET['action'] ?? '';

try {
    $pdo = getDBConnection();

    switch ($action) {
        case 'list':
            // Retornar lista vacía por ahora
            echo json_encode([
                'subjects' => []
            ]);
            break;

        default:
            echo json_encode(['error' => 'Acción no válida']);
    }

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>