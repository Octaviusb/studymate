<?php
// Performance Middleware - StudyMate SaaS
class PerformanceMiddleware {
    
    public static function enableCompression() {
        // Habilitar compresión GZIP
        if (!ob_get_level()) {
            ob_start('ob_gzhandler');
        }
        
        // Headers de compresión
        header('Content-Encoding: gzip');
        header('Vary: Accept-Encoding');
    }
    
    public static function setCacheHeaders($maxAge = 3600) {
        // Cache headers para archivos estáticos
        header("Cache-Control: public, max-age=$maxAge");
        header("Expires: " . gmdate('D, d M Y H:i:s', time() + $maxAge) . ' GMT');
        header("Last-Modified: " . gmdate('D, d M Y H:i:s', filemtime(__FILE__)) . ' GMT');
        
        // ETag para validación de cache
        $etag = md5_file(__FILE__);
        header("ETag: \"$etag\"");
        
        // Verificar si el cliente tiene cache válido
        if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] === "\"$etag\"") {
            http_response_code(304);
            exit;
        }
    }
    
    public static function optimizeResponse($data) {
        // Minimizar JSON eliminando espacios
        if (is_array($data) || is_object($data)) {
            return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        return $data;
    }
    
    public static function enableCORS() {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        header('Access-Control-Max-Age: 86400'); // Cache preflight por 24 horas
    }
    
    public static function rateLimit($identifier, $maxRequests = 100, $timeWindow = 3600) {
        $cacheFile = sys_get_temp_dir() . "/rate_limit_$identifier";
        
        if (file_exists($cacheFile)) {
            $data = json_decode(file_get_contents($cacheFile), true);
            
            // Limpiar ventana de tiempo expirada
            if (time() - $data['start_time'] > $timeWindow) {
                $data = ['count' => 0, 'start_time' => time()];
            }
            
            if ($data['count'] >= $maxRequests) {
                http_response_code(429);
                header('Retry-After: ' . ($timeWindow - (time() - $data['start_time'])));
                echo json_encode(['error' => 'Rate limit exceeded']);
                exit;
            }
            
            $data['count']++;
        } else {
            $data = ['count' => 1, 'start_time' => time()];
        }
        
        file_put_contents($cacheFile, json_encode($data));
    }
    
    public static function measurePerformance($startTime = null) {
        if ($startTime === null) {
            return microtime(true);
        }
        
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // en milisegundos
        
        header("X-Execution-Time: {$executionTime}ms");
        header("X-Memory-Usage: " . memory_get_peak_usage(true) . " bytes");
        
        return $executionTime;
    }
}

// Auto-aplicar optimizaciones básicas
PerformanceMiddleware::enableCompression();
PerformanceMiddleware::enableCORS();

// Función helper para APIs
function optimizedResponse($data, $cacheTime = 300) {
    PerformanceMiddleware::setCacheHeaders($cacheTime);
    
    header('Content-Type: application/json; charset=utf-8');
    echo PerformanceMiddleware::optimizeResponse($data);
    exit;
}

// Función para medir performance de APIs
function startPerformanceTimer() {
    return PerformanceMiddleware::measurePerformance();
}

function endPerformanceTimer($startTime) {
    return PerformanceMiddleware::measurePerformance($startTime);
}
?>