<?php
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$action = $_GET['action'] ?? '';

try {
    $pdo = getDBConnection();
    
    switch ($action) {
        case 'list':
            $stmt = $pdo->query("
                SELECT o.*, COUNT(u.id) as user_count 
                FROM organizations o 
                LEFT JOIN users u ON o.id = u.organization_id 
                GROUP BY o.id 
                ORDER BY o.id
            ");
            $organizations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($organizations as &$org) {
                $org['max_users'] = $org['max_users'] ?? 100; // Usar valor de BD o por defecto
                $org['plan'] = $org['plan'] ?? 'basic'; // Usar valor de BD o por defecto
            }
            
            echo json_encode(['success' => true, 'organizations' => $organizations]);
            break;
            
        case 'create':
            $input = json_decode(file_get_contents('php://input'), true);

            if (!$input || !isset($input['name'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Nombre de organización requerido']);
                break;
            }

            $plan = $input['plan'] ?? 'basic';
            $maxUsers = $input['max_users'] ?? 100;

            $stmt = $pdo->prepare("
                INSERT INTO organizations (name, domain, plan, max_users, status)
                VALUES (?, ?, ?, ?, 'active')
            ");

            $result = $stmt->execute([
                $input['name'],
                $input['domain'] ?? null,
                $plan,
                $maxUsers
            ]);

            if ($result) {
                echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error creando organización']);
            }
            break;
            
        case 'delete':
            $id = $_GET['id'] ?? 0;

            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID de organización requerido']);
                break;
            }

            try {
                // Verificar que la organización existe
                $stmt = $pdo->prepare("SELECT id, name FROM organizations WHERE id = :id");
                $stmt->execute(['id' => $id]);
                $org = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$org) {
                    echo json_encode(['success' => false, 'message' => 'Organización no encontrada']);
                    break;
                }

                // Intentar eliminar directamente (InfinityFree puede tener restricciones)
                $stmt = $pdo->prepare("DELETE FROM organizations WHERE id = :id");
                $result = $stmt->execute(['id' => $id]);

                if ($result) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Organización "' . $org['name'] . '" eliminada exitosamente'
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error eliminando organización']);
                }

            } catch (Exception $e) {
                // Si hay error de clave foránea, intentar con SET FOREIGN_KEY_CHECKS
                try {
                    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
                    $stmt = $pdo->prepare("DELETE FROM organizations WHERE id = :id");
                    $result = $stmt->execute(['id' => $id]);
                    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

                    if ($result) {
                        echo json_encode([
                            'success' => true,
                            'message' => 'Organización "' . $org['name'] . '" eliminada exitosamente (con restricciones desactivadas)'
                        ]);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Error eliminando organización']);
                    }
                } catch (Exception $e2) {
                    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1'); // Asegurar que se reactive
                    http_response_code(500);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Error eliminando organización: ' . $e2->getMessage()
                    ]);
                }
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
