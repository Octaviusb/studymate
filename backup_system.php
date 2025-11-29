<?php
require_once 'config.php';

class BackupSystem {
    private $pdo;
    private $backupDir;
    
    public function __construct() {
        $this->pdo = getDBConnection();
        $this->backupDir = __DIR__ . '/backups/';
        
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }
    
    public function createBackup($organizationId = null) {
        try {
            $timestamp = date('Y-m-d_H-i-s');
            $filename = $organizationId ? 
                "backup_org_{$organizationId}_{$timestamp}.sql" : 
                "backup_full_{$timestamp}.sql";
            
            $filepath = $this->backupDir . $filename;
            
            // Obtener estructura de tablas
            $tables = $this->getTables();
            $backup = "-- StudyMate SaaS Backup\n";
            $backup .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
            
            foreach ($tables as $table) {
                $backup .= $this->backupTable($table, $organizationId);
            }
            
            file_put_contents($filepath, $backup);
            
            return [
                'success' => true,
                'filename' => $filename,
                'size' => filesize($filepath),
                'path' => $filepath
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    private function getTables() {
        $stmt = $this->pdo->query("SHOW TABLES");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    private function backupTable($table, $organizationId = null) {
        $backup = "\n-- Table: $table\n";
        
        // Estructura de la tabla
        $stmt = $this->pdo->query("SHOW CREATE TABLE `$table`");
        $create = $stmt->fetch(PDO::FETCH_ASSOC);
        $backup .= $create['Create Table'] . ";\n\n";
        
        // Datos de la tabla
        $whereClause = '';
        if ($organizationId && in_array($table, ['users', 'subjects', 'student_grades', 'tasks'])) {
            $whereClause = " WHERE organization_id = $organizationId";
        }
        
        $stmt = $this->pdo->query("SELECT * FROM `$table`$whereClause");
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $backup .= "INSERT INTO `$table` VALUES (";
            $values = array_map(function($value) {
                return $value === null ? 'NULL' : "'" . addslashes($value) . "'";
            }, array_values($row));
            $backup .= implode(', ', $values) . ");\n";
        }
        
        return $backup;
    }
    
    public function listBackups() {
        $backups = [];
        $files = glob($this->backupDir . '*.sql');
        
        foreach ($files as $file) {
            $backups[] = [
                'filename' => basename($file),
                'size' => filesize($file),
                'date' => date('Y-m-d H:i:s', filemtime($file))
            ];
        }
        
        return $backups;
    }
    
    public function deleteBackup($filename) {
        $filepath = $this->backupDir . $filename;
        if (file_exists($filepath)) {
            return unlink($filepath);
        }
        return false;
    }
}

// API Endpoint
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_GET['action'] ?? '';
    $backup = new BackupSystem();
    
    switch ($action) {
        case 'create':
            $input = json_decode(file_get_contents('php://input'), true);
            $orgId = $input['organization_id'] ?? null;
            $result = $backup->createBackup($orgId);
            echo json_encode($result);
            break;
            
        case 'list':
            $backups = $backup->listBackups();
            echo json_encode(['success' => true, 'backups' => $backups]);
            break;
            
        case 'delete':
            $input = json_decode(file_get_contents('php://input'), true);
            $filename = $input['filename'] ?? '';
            $result = $backup->deleteBackup($filename);
            echo json_encode(['success' => $result]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    }
}
?>