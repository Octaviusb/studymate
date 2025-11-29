<?php
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$action = $_GET['action'] ?? '';

try {
    $pdo = getDBConnection();

    switch ($action) {
        case 'get_users':
            $orgId = $_GET['organization_id'] ?? '';
            $role = $_GET['role'] ?? '';

            $sql = "SELECT id, username, email, full_name, role, grade_level, status, organization_id FROM users WHERE 1=1";
            $params = [];

            if ($orgId) {
                $sql .= " AND organization_id = ?";
                $params[] = $orgId;
            }

            if ($role) {
                $sql .= " AND role = ?";
                $params[] = $role;
            }

            $sql .= " ORDER BY id DESC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'users' => $users]);
            break;

        case 'create_user':
            $input = json_decode(file_get_contents('php://input'), true);

            $stmt = $pdo->prepare("
                INSERT INTO users (organization_id, username, email, password_hash, full_name, role, grade_level, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'active')
            ");

            $passwordHash = password_hash('123456', PASSWORD_DEFAULT);

            $result = $stmt->execute([
                $input['organization_id'],
                $input['username'] ?? $input['email'],
                $input['email'],
                $passwordHash,
                $input['full_name'],
                $input['role'],
                $input['grade_level'] ?? null
            ]);

            if ($result) {
                echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error creando usuario']);
            }
            break;

        case 'update_user':
            $input = json_decode(file_get_contents('php://input'), true);

            $stmt = $pdo->prepare("
                UPDATE users SET
                    full_name = ?,
                    email = ?,
                    grade_level = ?,
                    status = ?,
                    role = ?
                WHERE id = ?
            ");

            $result = $stmt->execute([
                $input['full_name'],
                $input['email'],
                $input['grade_level'],
                $input['status'],
                $input['role'],
                $input['id']
            ]);

            echo json_encode(['success' => $result]);
            break;

        case 'delete_user':
            $id = $_GET['id'] ?? 0;

            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $result = $stmt->execute([$id]);

            echo json_encode(['success' => $result]);
            break;

        case 'withdraw_student':
            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['id'] ?? 0;
            $reason = $input['reason'] ?? null;

            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID de estudiante requerido']);
                break;
            }

            try {
                $stmt = $pdo->prepare("UPDATE users SET status = 'withdrawn' WHERE id = ?");
                $result = $stmt->execute([$id]);

                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Estudiante retirado (status=withdrawn)']);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Estudiante no encontrado']);
                }
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Error al retirar estudiante: ' . $e->getMessage()]);
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