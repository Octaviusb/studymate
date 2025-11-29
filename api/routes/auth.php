<?php
// Security headers
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
}

// Start output buffering to catch any accidental output
ob_start();

require_once dirname(__DIR__) . '/config.php';

// Set headers
header('Content-Type: application/json; charset=utf-8');
// Allow development origins
$allowedOrigins = [
    'http://localhost:3000',
    'http://127.0.0.1:5500',
    'http://localhost:5500',
    'https://studymate.gt.tc',
    'https://studymate-saas.azurewebsites.net'
];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins)) {
    header('Access-Control-Allow-Origin: ' . $origin);
} else {
    header('Access-Control-Allow-Origin: *'); // Fallback for development
}
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Get request method and path
$method = $_SERVER['REQUEST_METHOD'];
$path = str_replace('/auth', '', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

if ($method === 'POST' && $path === '/login') {
    handleLogin();
} elseif ($method === 'POST' && $path === '/register') {
    handleRegister();
} else {
    http_response_code(404);
    echo json_encode(['message' => 'Endpoint no encontrado']);
}

function sendJsonResponse($data, $statusCode = 200) {
    // Clear any previous output
    if (ob_get_level() > 0) {
        ob_clean();
    }
    
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function handleError($message, $statusCode = 400, $errorDetails = null) {
    $response = [
        'success' => false,
        'message' => $message
    ];
    
    if ($errorDetails !== null) {
        error_log('API Error: ' . filter_var($message, FILTER_SANITIZE_STRING) . ' - ' . filter_var(json_encode($errorDetails), FILTER_SANITIZE_STRING));
    } else {
        error_log('API Error: ' . filter_var($message, FILTER_SANITIZE_STRING));
    }
    
    sendJsonResponse($response, $statusCode);
}

function handleLogin() {
    $rawInput = file_get_contents('php://input');
    if (strlen($rawInput) > 10240) { // 10KB limit
        handleError('Request too large');
        return;
    }
    $input = json_decode($rawInput, true);

    if (!$input || !isset($input['email']) || !isset($input['password'])) {
        handleError('Email y contraseña requeridos');
        return;
    }

    $email = trim($input['email']);
    $password = $input['password'];

    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT u.id, u.username, u.email, u.password_hash, u.full_name, u.role, u.grade_level, u.organization_id, o.name as organization_name, o.plan FROM users u LEFT JOIN organizations o ON u.organization_id = o.id WHERE u.email = ? AND u.status = 'active'");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            http_response_code(401);
            echo json_encode(['message' => 'Credenciales inválidas']);
            return;
        }

        // Generate token with user role
        $tokenData = [
            'userId' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role'] ?? 'student',
            'exp' => time() + (24 * 60 * 60) // 24 hours
        ];
        $token = base64_encode(json_encode($tokenData));

        // Prepare user data to return
        $userData = [
            'id' => $user['id'],
            'name' => $user['full_name'],
            'email' => $user['email'],
            'role' => $user['role'] ?? 'student',
            'organization_id' => $user['organization_id'],
            'organization_name' => $user['organization_name'],
            'plan' => $user['plan']
        ];

        // Add role-specific data
        if (isset($user['grade_level'])) {
            $userData['grade'] = $user['grade_level'];
        }

        echo json_encode([
            'token' => $token,
            'user' => $userData
        ]);

    } catch (PDOException $e) {
        handleError('Error de base de datos', 500, $e->getMessage());
    } catch (Exception $e) {
        handleError('Error interno del servidor', 500, $e->getMessage());
    }
}

function handleRegister() {
    // Get and validate JSON input
    $json = file_get_contents('php://input');
    if (empty($json) || strlen($json) > 10240) {
        handleError('Datos inválidos o demasiado grandes');
        return;
    }
    
    $input = json_decode($json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        handleError('Formato JSON inválido: ' . json_last_error_msg());
    }

    // Validate required fields
    $requiredFields = ['name', 'email', 'password', 'role'];
    $missingFields = [];
    foreach ($requiredFields as $field) {
        if (!isset($input[$field]) || trim(filter_var($input[$field], FILTER_SANITIZE_STRING)) === '') {
            $missingFields[] = $field;
        }
    }
    
    if (!empty($missingFields)) {
        handleError('Faltan campos requeridos: ' . implode(', ', $missingFields));
    }

    $name = trim(filter_var($input['name'], FILTER_SANITIZE_STRING));
    $email = trim(filter_var($input['email'], FILTER_SANITIZE_EMAIL));
    $password = $input['password'];
    $role = strtolower(trim(filter_var($input['role'], FILTER_SANITIZE_STRING)));
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        handleError('Formato de email inválido');
        return;
    }
    
    // Validate role
    if (!in_array($role, ['student', 'teacher'])) {
        http_response_code(400);
        echo json_encode(['message' => 'Rol no válido']);
        return;
    }

    // Set role-specific defaults
    $grade = $role === 'student' ? ($input['grade'] ?? '11°') : null;
    $organizationId = filter_var($input['organization_id'] ?? 1, FILTER_VALIDATE_INT); // Default to demo org
    
    $pdo = getDBConnection();

    // Validate organization exists and is active
    $stmt = $pdo->prepare("SELECT id FROM organizations WHERE id = ? AND status = 'active'");
    $stmt->execute([$organizationId]);
    if (!$stmt->fetchColumn()) {
        $organizationId = 1; // Fallback to demo org
    }

    if (strlen($password) < 6) {
        http_response_code(400);
        echo json_encode(['message' => 'La contraseña debe tener al menos 6 caracteres']);
        return;
    }

    try {

        // Check if user already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['message' => 'El correo electrónico ya está registrado']);
            return;
        }

        // Hash password
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // Insert new user with role and organization
        $stmt = $pdo->prepare("
            INSERT INTO users 
            (organization_id, username, email, password_hash, full_name, grade_level, role, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'active')
        ");
        $stmt->execute([
            $organizationId,
            $email, 
            $email, 
            $passwordHash, 
            $name, 
            $grade,
            $role
        ]);

        $userId = $pdo->lastInsertId();

        // Generate token with role
        $tokenData = [
            'userId' => $userId,
            'email' => $email,
            'role' => $role,
            'exp' => time() + (24 * 60 * 60) // 24 hours
        ];
        $token = base64_encode(json_encode($tokenData));

        // Get organization info
        $stmt = $pdo->prepare("SELECT name, plan FROM organizations WHERE id = ?");
        $stmt->execute([$organizationId]);
        $orgInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Prepare user data to return
        $userData = [
            'id' => $userId,
            'name' => $name,
            'email' => $email,
            'role' => $role,
            'organization_id' => $organizationId,
            'organization_name' => $orgInfo['name'] ?? 'Demo School',
            'plan' => $orgInfo['plan'] ?? 'free'
        ];

        // Add role-specific data
        if ($role === 'student') {
            $userData['grade'] = $grade;
        }

        echo json_encode([
            'token' => $token,
            'user' => $userData
        ]);

    } catch (PDOException $e) {
        handleError('Error de base de datos', 500, $e->getMessage());
    } catch (Exception $e) {
        handleError('Error interno del servidor', 500, $e->getMessage());
    }
}
?>
