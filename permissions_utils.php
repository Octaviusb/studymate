<?php
/**
 * Utilidades para manejo de permisos
 */

class PermissionsManager {
    
    public static function hasPermission($userId, $permission) {
        try {
            $pdo = getDBConnection();
            
            // Obtener rol del usuario
            $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ? AND status = 'active'");
            $stmt->execute([$userId]);
            $userRole = $stmt->fetchColumn();
            
            if (!$userRole) return false;
            
            // Super admin tiene todos los permisos
            if ($userRole === 'super_admin') return true;
            
            // Verificar permiso específico
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM permissions WHERE role = ? AND permission = ?");
            $stmt->execute([$userRole, $permission]);
            
            return $stmt->fetchColumn() > 0;
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    public static function getUserRole($userId) {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ? AND status = 'active'");
            $stmt->execute([$userId]);
            return $stmt->fetchColumn();
        } catch (Exception $e) {
            return false;
        }
    }
    
    public static function canModifyGrades($userId) {
        return self::hasPermission($userId, 'modify_grades');
    }
    
    public static function canCreateGrades($userId) {
        return self::hasPermission($userId, 'create_grades') || self::hasPermission($userId, 'modify_grades');
    }
    
    public static function canViewAllReports($userId) {
        return self::hasPermission($userId, 'view_all_reports');
    }
    
    public static function canManageUsers($userId) {
        return self::hasPermission($userId, 'manage_users') || self::hasPermission($userId, 'manage_all_users');
    }
    
    public static function requirePermission($userId, $permission) {
        if (!self::hasPermission($userId, $permission)) {
            http_response_code(403);
            echo json_encode(['message' => 'Permisos insuficientes para esta acción']);
            exit;
        }
    }
}
?>