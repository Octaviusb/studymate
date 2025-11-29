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
            $orgFilter = $_GET['org_id'] ?? '';
            $roleFilter = $_GET['role'] ?? '';
            $userId = $_GET['user_id'] ?? '';

            // Verificar permisos del usuario - permitir super_admin siempre
            if ($userId) {
                $userCheck = $pdo->prepare("SELECT role FROM users WHERE id = ?");
                $userCheck->execute([$userId]);
                $userRole = $userCheck->fetchColumn();

                // Si es super_admin, permitir acceso
                if ($userRole === 'super_admin') {
                    // Acceso completo
                } elseif (!$userRole || !in_array($userRole, ['admin'])) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
                    break;
                }
            }

            $sql = "
                SELECT u.*, o.name as organization_name
                FROM users u
                LEFT JOIN organizations o ON u.organization_id = o.id
                WHERE 1=1
            ";
            $params = [];

            if ($orgFilter) {
                $sql .= " AND u.organization_id = ?";
                $params[] = $orgFilter;
            }

            if ($roleFilter) {
                $sql .= " AND u.role = ?";
                $params[] = $roleFilter;
            }

            $sql .= " ORDER BY u.id DESC LIMIT 100";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'users' => $users]);
            break;
            
        case 'delete_user':
            $userId = $_GET['id'] ?? '';

            if (!$userId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID de usuario requerido']);
                break;
            }

            // Verificar que no se elimine al superadmin
            if ($userId == 999) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'No se puede eliminar al superadmin']);
                break;
            }

            try {
                // Eliminar usuario (las restricciones de clave foránea harán el resto)
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$userId]);

                if ($stmt->rowCount() > 0) {
                    echo json_encode(['success' => true, 'message' => 'Usuario eliminado exitosamente']);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
                }
            } catch (Exception $e) {
                // Si hay error de clave foránea, intentar eliminación forzada
                if (strpos($e->getMessage(), 'foreign key constraint') !== false) {
                    try {
                        // Desactivar restricciones de clave foránea temporalmente
                        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

                        // Eliminar registros relacionados primero
                        $pdo->prepare("DELETE FROM payments WHERE student_id = ?")->execute([$userId]);
                        $pdo->prepare("DELETE FROM grades WHERE student_id = ?")->execute([$userId]);
                        $pdo->prepare("DELETE FROM tasks WHERE student_id = ? OR teacher_id = ?")->execute([$userId, $userId]);
                        $pdo->prepare("DELETE FROM attendance WHERE student_id = ? OR teacher_id = ?")->execute([$userId, $userId]);

                        // Ahora eliminar el usuario
                        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                        $stmt->execute([$userId]);

                        // Reactivar restricciones
                        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

                        echo json_encode(['success' => true, 'message' => 'Usuario y datos relacionados eliminados exitosamente']);
                    } catch (Exception $e2) {
                        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1'); // Asegurar que se reactive
                        http_response_code(500);
                        echo json_encode(['success' => false, 'message' => 'Error al eliminar usuario y dependencias: ' . $e2->getMessage()]);
                    }
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Error al eliminar usuario: ' . $e->getMessage()]);
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