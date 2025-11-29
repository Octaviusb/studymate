<?php
/**
 * StudyMate Security Utilities
 * Funciones de seguridad centralizadas
 */

class SecurityUtils {
    
    /**
     * Valida y sanitiza entrada de usuario
     */
    public static function sanitizeInput($input, $type = 'string') {
        if (is_null($input)) return null;
        
        switch ($type) {
            case 'email':
                return filter_var(trim($input), FILTER_SANITIZE_EMAIL);
            case 'int':
                return filter_var($input, FILTER_VALIDATE_INT);
            case 'url':
                return filter_var(trim($input), FILTER_SANITIZE_URL);
            case 'string':
            default:
                return filter_var(trim($input), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        }
    }
    
    /**
     * Valida token JWT básico
     */
    public static function validateToken($token) {
        if (empty($token)) return false;
        
        try {
            $decoded = base64_decode($token);
            $data = json_decode($decoded, true);
            
            if (!$data || !isset($data['userId']) || !isset($data['exp'])) {
                return false;
            }
            
            if ($data['exp'] < time()) {
                return false; // Token expirado
            }
            
            return $data;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Genera token CSRF
     */
    public static function generateCSRFToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Valida token CSRF
     */
    public static function validateCSRFToken($token) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Rate limiting básico
     */
    public static function checkRateLimit($identifier, $maxRequests = 60, $timeWindow = 3600) {
        $cacheFile = sys_get_temp_dir() . '/studymate_rate_' . md5($identifier);
        
        if (!file_exists($cacheFile)) {
            file_put_contents($cacheFile, json_encode(['count' => 1, 'reset' => time() + $timeWindow]));
            return true;
        }
        
        $data = json_decode(file_get_contents($cacheFile), true);
        
        if (time() > $data['reset']) {
            file_put_contents($cacheFile, json_encode(['count' => 1, 'reset' => time() + $timeWindow]));
            return true;
        }
        
        if ($data['count'] >= $maxRequests) {
            return false;
        }
        
        $data['count']++;
        file_put_contents($cacheFile, json_encode($data));
        return true;
    }
    
    /**
     * Logs de seguridad
     */
    public static function logSecurityEvent($event, $details = []) {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => $event,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'details' => $details
        ];
        
        error_log('SECURITY: ' . json_encode($logEntry));
    }
}
?>