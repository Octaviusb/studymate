<?php
/**
 * Multi-Tenant Utilities
 * Gestión de organizaciones y contexto de tenant
 */

class TenantManager {
    
    /**
     * Obtiene la organización del usuario
     */
    public static function getUserOrganization($userId) {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT organization_id FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    }
    
    /**
     * Valida que el usuario pertenezca a la organización
     */
    public static function validateUserAccess($userId, $organizationId) {
        $userOrgId = self::getUserOrganization($userId);
        return $userOrgId == $organizationId;
    }
    
    /**
     * Obtiene información de la organización
     */
    public static function getOrganization($organizationId) {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM organizations WHERE id = ? AND status = 'active'");
        $stmt->execute([$organizationId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Verifica límites del plan
     */
    public static function checkPlanLimits($organizationId, $feature) {
        $org = self::getOrganization($organizationId);
        if (!$org) return false;
        
        switch ($feature) {
            case 'max_users':
                $stmt = getDBConnection()->prepare("SELECT COUNT(*) FROM users WHERE organization_id = ?");
                $stmt->execute([$organizationId]);
                $currentUsers = $stmt->fetchColumn();
                return $currentUsers < $org['max_users'];
                
            case 'ai_chat':
                return in_array($org['plan'], ['basic', 'premium']);
                
            case 'analytics':
                return $org['plan'] === 'premium';
                
            default:
                return true;
        }
    }
    
    /**
     * Middleware para validar tenant en requests
     */
    public static function validateTenantRequest($userId, $requiredRole = null) {
        $organizationId = self::getUserOrganization($userId);
        
        if (!$organizationId) {
            http_response_code(403);
            echo json_encode(['message' => 'Usuario sin organización asignada']);
            exit;
        }
        
        $org = self::getOrganization($organizationId);
        if (!$org) {
            http_response_code(403);
            echo json_encode(['message' => 'Organización inactiva']);
            exit;
        }
        
        if ($requiredRole) {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $userRole = $stmt->fetchColumn();
            
            $roleHierarchy = ['student' => 1, 'teacher' => 2, 'admin' => 3, 'super_admin' => 4];
            
            if ($roleHierarchy[$userRole] < $roleHierarchy[$requiredRole]) {
                http_response_code(403);
                echo json_encode(['message' => 'Permisos insuficientes']);
                exit;
            }
        }
        
        return $organizationId;
    }
}
?>