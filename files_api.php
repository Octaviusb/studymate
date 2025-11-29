<?php
require_once 'config.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$action = $_GET['action'] ?? '';
$uploadDir = __DIR__ . '/uploads/';

// Crear directorio si no existe
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

try {
    $pdo = getDBConnection();
    
    // Crear tabla de archivos si no existe
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS files (
            id INT AUTO_INCREMENT PRIMARY KEY,
            organization_id INT NOT NULL,
            user_id INT NOT NULL,
            filename VARCHAR(255) NOT NULL,
            original_name VARCHAR(255) NOT NULL,
            file_type VARCHAR(100),
            file_size INT,
            file_path VARCHAR(500),
            category ENUM('document', 'image', 'certificate', 'report') DEFAULT 'document',
            uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    
    switch ($action) {
        case 'upload':
            if (!isset($_FILES['file'])) {
                throw new Exception('No se recibió archivo');
            }
            
            $file = $_FILES['file'];
            $orgId = $_POST['organization_id'] ?? 0;
            $userId = $_POST['user_id'] ?? 0;
            $category = $_POST['category'] ?? 'document';
            
            // Validar archivo
            $allowedTypes = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'xlsx', 'xls'];
            $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if (!in_array($fileExt, $allowedTypes)) {
                throw new Exception('Tipo de archivo no permitido');
            }
            
            if ($file['size'] > 10 * 1024 * 1024) { // 10MB max
                throw new Exception('Archivo muy grande (máximo 10MB)');
            }
            
            // Generar nombre único
            $filename = uniqid() . '_' . time() . '.' . $fileExt;
            $filepath = $uploadDir . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                // Guardar en base de datos
                $stmt = $pdo->prepare("
                    INSERT INTO files (organization_id, user_id, filename, original_name, file_type, file_size, file_path, category) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $orgId, $userId, $filename, $file['name'], 
                    $file['type'], $file['size'], $filepath, $category
                ]);
                
                echo json_encode([
                    'success' => true,
                    'file_id' => $pdo->lastInsertId(),
                    'filename' => $filename
                ]);
            } else {
                throw new Exception('Error subiendo archivo');
            }
            break;
            
        case 'list':
            $orgId = $_GET['organization_id'] ?? 0;
            $category = $_GET['category'] ?? '';
            
            $sql = "SELECT * FROM files WHERE organization_id = ?";
            $params = [$orgId];
            
            if ($category) {
                $sql .= " AND category = ?";
                $params[] = $category;
            }
            
            $sql .= " ORDER BY uploaded_at DESC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'files' => $files]);
            break;
            
        case 'download':
            $fileId = $_GET['file_id'] ?? 0;
            
            $stmt = $pdo->prepare("SELECT * FROM files WHERE id = ?");
            $stmt->execute([$fileId]);
            $file = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($file && file_exists($file['file_path'])) {
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . $file['original_name'] . '"');
                header('Content-Length: ' . filesize($file['file_path']));
                readfile($file['file_path']);
                exit;
            } else {
                throw new Exception('Archivo no encontrado');
            }
            break;
            
        case 'delete':
            $fileId = $_GET['file_id'] ?? 0;
            
            $stmt = $pdo->prepare("SELECT * FROM files WHERE id = ?");
            $stmt->execute([$fileId]);
            $file = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($file) {
                // Eliminar archivo físico
                if (file_exists($file['file_path'])) {
                    unlink($file['file_path']);
                }
                
                // Eliminar registro
                $stmt = $pdo->prepare("DELETE FROM files WHERE id = ?");
                $stmt->execute([$fileId]);
                
                echo json_encode(['success' => true]);
            } else {
                throw new Exception('Archivo no encontrado');
            }
            break;
            
        default:
            throw new Exception('Acción no válida');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>