<?php
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$action = $_GET['action'] ?? '';

try {
    $pdo = getDBConnection();

    switch ($action) {
        case 'get_users_paginated':
            $orgId = $_GET['organization_id'] ?? '';
            $role = $_GET['role'] ?? '';
            $search = $_GET['search'] ?? '';
            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 10);
            $offset = ($page - 1) * $limit;

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

            if ($search) {
                $sql .= " AND (full_name LIKE ? OR email LIKE ? OR username LIKE ?)";
                $searchTerm = "%$search%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }

            // Contar total
            $countSql = str_replace("SELECT id, username, email, full_name, role, grade_level, status, organization_id FROM users", "SELECT COUNT(*) FROM users", $sql);
            $stmt = $pdo->prepare($countSql);
            $stmt->execute($params);
            $total = $stmt->fetchColumn();

            // Obtener datos con paginación
            $sql .= " ORDER BY id DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'data' => $users,
                'pagination' => [
                    'total' => $total,
                    'per_page' => $limit,
                    'current_page' => $page,
                    'total_pages' => ceil($total / $limit)
                ]
            ]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>