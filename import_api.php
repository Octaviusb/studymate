<?php
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$action = $_GET['action'] ?? '';

try {
    $pdo = getDBConnection();
    
    switch ($action) {
        case 'import_students':
            if (!isset($_FILES['csv_file'])) {
                throw new Exception('Archivo CSV requerido');
            }
            
            $file = $_FILES['csv_file']['tmp_name'];
            $orgId = $_POST['organization_id'] ?? 0;
            
            if (($handle = fopen($file, "r")) !== FALSE) {
                $imported = 0;
                $errors = [];
                
                // Saltar header
                fgetcsv($handle);
                
                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    try {
                        $fullName = $data[0] ?? '';
                        $email = $data[1] ?? '';
                        $grade = $data[2] ?? '';
                        
                        if (!$fullName || !$email) {
                            $errors[] = "Fila con datos incompletos: $fullName";
                            continue;
                        }
                        
                        $username = strtolower(str_replace(' ', '.', $fullName));
                        $passwordHash = password_hash('123456', PASSWORD_DEFAULT);
                        
                        $stmt = $pdo->prepare("
                            INSERT INTO users (organization_id, username, email, password_hash, full_name, role, grade_level, status) 
                            VALUES (?, ?, ?, ?, ?, 'student', ?, 'active')
                        ");
                        
                        $stmt->execute([$orgId, $username, $email, $passwordHash, $fullName, $grade]);
                        $imported++;
                        
                    } catch (Exception $e) {
                        $errors[] = "Error importando $fullName: " . $e->getMessage();
                    }
                }
                
                fclose($handle);
                
                echo json_encode([
                    'success' => true, 
                    'imported' => $imported,
                    'errors' => $errors
                ]);
            } else {
                throw new Exception('No se pudo leer el archivo');
            }
            break;
            
        case 'import_teachers':
            $input = json_decode(file_get_contents('php://input'), true);
            $teachers = $input['teachers'] ?? [];
            $orgId = $input['organization_id'] ?? 0;
            
            $imported = 0;
            $errors = [];
            
            foreach ($teachers as $teacher) {
                try {
                    $fullName = $teacher['name'] ?? '';
                    $email = $teacher['email'] ?? '';
                    $subject = $teacher['subject'] ?? '';
                    
                    if (!$fullName || !$email) {
                        $errors[] = "Datos incompletos para: $fullName";
                        continue;
                    }
                    
                    $username = strtolower($subject);
                    $passwordHash = password_hash('123456', PASSWORD_DEFAULT);
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO users (organization_id, username, email, password_hash, full_name, role, status) 
                        VALUES (?, ?, ?, ?, ?, 'teacher', 'active')
                    ");
                    
                    $stmt->execute([$orgId, $username, $email, $passwordHash, $fullName]);
                    $imported++;
                    
                } catch (Exception $e) {
                    $errors[] = "Error importando $fullName: " . $e->getMessage();
                }
            }
            
            echo json_encode([
                'success' => true,
                'imported' => $imported,
                'errors' => $errors
            ]);
            break;
            
        default:
            throw new Exception('Acción no válida');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>