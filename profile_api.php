<?php
require_once 'config.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$userId = $_GET['user_id'] ?? null;

if (!$userId) {
    http_response_code(400);
    echo json_encode(['message' => 'ID de usuario requerido']);
    exit;
}

// Validar que el usuario existe
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND status = 'active'");
    $stmt->execute([$userId]);
    if (!$stmt->fetchColumn()) {
        http_response_code(404);
        echo json_encode(['message' => 'Usuario no encontrado']);
        exit;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Error de validación']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    if ($action === 'get') {
        // Obtener perfil del usuario
        $stmt = $pdo->prepare("
            SELECT up.*, u.full_name, u.email, u.role, u.organization_id
            FROM user_profiles up
            RIGHT JOIN users u ON up.user_id = u.id
            WHERE u.id = ? AND u.status = 'active'
        ");
        $stmt->execute([$userId]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($profile) {
            echo json_encode(['profile' => $profile]);
        } else {
            echo json_encode(['profile' => null, 'message' => 'Perfil no encontrado']);
        }
        
    } elseif ($action === 'upload_photo') {
        // Subir foto de perfil
        if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['message' => 'No se recibió ninguna foto válida']);
            exit;
        }
        
        $file = $_FILES['photo'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($file['type'], $allowedTypes)) {
            http_response_code(400);
            echo json_encode(['message' => 'Tipo de archivo no permitido. Use JPG, PNG o GIF']);
            exit;
        }
        
        if ($file['size'] > $maxSize) {
            http_response_code(400);
            echo json_encode(['message' => 'El archivo es muy grande. Máximo 5MB']);
            exit;
        }
        
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'profile_' . $userId . '_' . time() . '.' . $extension;
        $uploadPath = 'uploads/profile_photos/' . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            // Actualizar la base de datos
            $stmt = $pdo->prepare("UPDATE user_profiles SET profile_photo = ? WHERE user_id = ?");
            $stmt->execute([$uploadPath, $userId]);
            
            echo json_encode(['message' => 'Foto subida exitosamente', 'photo_url' => $uploadPath]);
        } else {
            http_response_code(500);
            echo json_encode(['message' => 'Error al subir la foto']);
        }
        
    } elseif ($action === 'save') {
        // Guardar perfil del usuario
        $rawInput = file_get_contents('php://input');
        $input = json_decode($rawInput, true);
        
        if (!$input) {
            http_response_code(400);
            echo json_encode(['message' => 'Datos inválidos']);
            exit;
        }
        
        // Obtener organización del usuario
        $stmt = $pdo->prepare("SELECT organization_id FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $orgId = $stmt->fetchColumn();
        
        if (!$orgId) {
            http_response_code(404);
            echo json_encode(['message' => 'Usuario no encontrado']);
            exit;
        }
        
        // Verificar si el perfil ya existe
        $stmt = $pdo->prepare("SELECT id FROM user_profiles WHERE user_id = ?");
        $stmt->execute([$userId]);
        $profileExists = $stmt->fetchColumn();
        
        if ($profileExists) {
            // Actualizar perfil existente
            $stmt = $pdo->prepare("
                UPDATE user_profiles SET
                    document_type = ?, document_number = ?, birth_date = ?, gender = ?,
                    phone = ?, mobile = ?, address = ?, city = ?, state = ?,
                    current_grade_id = ?, student_code = ?, enrollment_date = ?,
                    guardian_name = ?, guardian_document = ?, guardian_phone = ?, 
                    guardian_email = ?, guardian_relationship = ?,
                    emergency_contact_name = ?, emergency_contact_phone = ?, emergency_contact_relationship = ?,
                    blood_type = ?, allergies = ?, medical_conditions = ?,
                    profile_completed = TRUE, updated_at = CURRENT_TIMESTAMP
                WHERE user_id = ?
            ");
            $stmt->execute([
                $input['document_type'], $input['document_number'], $input['birth_date'], $input['gender'],
                $input['phone'], $input['mobile'], $input['address'], $input['city'], $input['state'],
                $input['current_grade_id'] ?: null, $input['student_code'], $input['enrollment_date'] ?: null,
                $input['guardian_name'], $input['guardian_document'], $input['guardian_phone'],
                $input['guardian_email'], $input['guardian_relationship'],
                $input['emergency_contact_name'], $input['emergency_contact_phone'], $input['emergency_contact_relationship'],
                $input['blood_type'], $input['allergies'], $input['medical_conditions'],
                $userId
            ]);
        } else {
            // Crear nuevo perfil
            $stmt = $pdo->prepare("
                INSERT INTO user_profiles (
                    user_id, organization_id, document_type, document_number, birth_date, gender,
                    phone, mobile, address, city, state,
                    current_grade_id, student_code, enrollment_date,
                    guardian_name, guardian_document, guardian_phone, guardian_email, guardian_relationship,
                    emergency_contact_name, emergency_contact_phone, emergency_contact_relationship,
                    blood_type, allergies, medical_conditions, profile_completed
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, TRUE)
            ");
            $stmt->execute([
                $userId, $orgId, $input['document_type'], $input['document_number'], $input['birth_date'], $input['gender'],
                $input['phone'], $input['mobile'], $input['address'], $input['city'], $input['state'],
                $input['current_grade_id'] ?: null, $input['student_code'], $input['enrollment_date'] ?: null,
                $input['guardian_name'], $input['guardian_document'], $input['guardian_phone'],
                $input['guardian_email'], $input['guardian_relationship'],
                $input['emergency_contact_name'], $input['emergency_contact_phone'], $input['emergency_contact_relationship'],
                $input['blood_type'], $input['allergies'], $input['medical_conditions']
            ]);
        }
        
        // Actualizar flag en tabla users
        $stmt = $pdo->prepare("UPDATE users SET profile_completed = TRUE, last_profile_update = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$userId]);
        
        echo json_encode(['message' => 'Perfil guardado exitosamente']);
        
    } else {
        http_response_code(404);
        echo json_encode(['message' => 'Acción no encontrada']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Error interno del servidor']);
}
?>