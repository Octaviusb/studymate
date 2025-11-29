<?php
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$action = $_GET['action'] ?? '';

try {
    $pdo = getDBConnection();

    switch ($action) {
        case 'recent':
            // Retornar lista vacía por ahora
            echo json_encode([
                'grades' => []
            ]);
            break;

        default:
            echo json_encode(['error' => 'Acción no válida']);
    }

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>