<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

if (!$input || !isset($input['email']) || !isset($input['password'])) {
    http_response_code(400);
    echo json_encode(['message' => 'Email y contraseña requeridos']);
    exit;
}

$email = trim($input['email']);
$password = $input['password'];

try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT u.id, u.username, u.email, u.password_hash, u.full_name, u.role, u.grade_level, u.organization_id, o.name as organization_name
        FROM users u
        LEFT JOIN organizations o ON u.organization_id = o.id
        WHERE u.email = ? AND u.status = 'active'
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password_hash'])) {
        // Verificar si es el superadmin con credenciales específicas
        if ($email === 'obuitragocamelo@yahoo.es' && $password === 'Ener19447/*') {
            $user = [
                'id' => 999,
                'username' => 'superadmin',
                'email' => 'obuitragocamelo@yahoo.es',
                'password_hash' => password_hash('Ener19447/*', PASSWORD_DEFAULT),
                'full_name' => 'Super Administrador',
                'role' => 'super_admin',
                'grade_level' => null,
                'organization_id' => null,
                'organization_name' => 'StudyMate SaaS'
            ];
        } else {
            http_response_code(401);
            echo json_encode(['message' => 'Credenciales inválidas']);
            exit;
        }
    }

    $tokenData = [
        'userId' => $user['id'],
        'email' => $user['email'],
        'role' => $user['role'] ?? 'student',
        'exp' => time() + (24 * 60 * 60)
    ];
    $token = base64_encode(json_encode($tokenData));

    $userData = [
        'id' => $user['id'],
        'name' => $user['full_name'],
        'email' => $user['email'],
        'role' => $user['role'] ?? 'student',
        'organization_id' => $user['organization_id'],
        'organization_name' => $user['organization_name']
    ];

    if (isset($user['grade_level'])) {
        $userData['grade'] = $user['grade_level'];
    }

    echo json_encode([
        'token' => $token,
        'user' => $userData
    ]);

} catch (PDOException $e) {
    error_log('Login DB Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['message' => 'Error de base de datos: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log('Login General Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['message' => 'Error interno del servidor: ' . $e->getMessage()]);
}
?>