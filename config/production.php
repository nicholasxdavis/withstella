<?php
/**
 * Production Configuration
 * Optimizations and security settings for production deployment
 */

// Production environment flag
define('PRODUCTION_MODE', true);

// Disable error reporting for production
if (PRODUCTION_MODE) {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
}

// OPcache configuration (should be set in php.ini, but documented here)
/*
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
opcache.revalidate_freq=2
opcache.fast_shutdown=1
opcache.enable_cli=1
*/

// Security headers
function setSecurityHeaders() {
    if (!headers_sent()) {
        // Prevent clickjacking
        header('X-Frame-Options: DENY');
        
        // Prevent MIME type sniffing
        header('X-Content-Type-Options: nosniff');
        
        // Enable XSS protection
        header('X-XSS-Protection: 1; mode=block');
        
        // Strict Transport Security (HTTPS only)
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
        
        // Content Security Policy
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://cdnjs.cloudflare.com https://www.googletagmanager.com; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://assets.vercel.com; img-src 'self' data: https:; font-src 'self' https://cdnjs.cloudflare.com https://assets.vercel.com; connect-src 'self';");
        
        // Referrer Policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Permissions Policy
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    }
}

// Set security headers
setSecurityHeaders();

// Production error handler
function productionErrorHandler($errno, $errstr, $errfile, $errline) {
    if (PRODUCTION_MODE) {
        // Log the error
        error_log("PHP Error: $errstr in $errfile on line $errline");
        
        // Return generic error message to user
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
        }
        return true;
    }
    return false;
}

// Set production error handler
if (PRODUCTION_MODE) {
    set_error_handler('productionErrorHandler');
}

// Production exception handler
function productionExceptionHandler($exception) {
    if (PRODUCTION_MODE) {
        // Log the exception
        error_log("Uncaught Exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine());
        
        // Return generic error message to user
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
        }
    }
}

// Set production exception handler
if (PRODUCTION_MODE) {
    set_exception_handler('productionExceptionHandler');
}

// Performance optimizations
if (PRODUCTION_MODE) {
    // Enable output compression
    if (extension_loaded('zlib') && !ob_get_level()) {
        ob_start('ob_gzhandler');
    }
    
    // Set cache headers for static assets
    function setCacheHeaders($maxAge = 31536000) { // 1 year
        if (!headers_sent()) {
            header("Cache-Control: public, max-age=$maxAge");
            header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $maxAge) . ' GMT');
        }
    }
}

// Database connection optimization for production
function getOptimizedDBConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        require_once __DIR__ . '/database.php';
        $config = $GLOBALS['dbConfig'];
        
        $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset=utf8mb4";
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];
        
        // Production-specific optimizations
        if (PRODUCTION_MODE) {
            $options[PDO::ATTR_PERSISTENT] = true; // Enable persistent connections
        }
        
        $pdo = new PDO($dsn, $config['username'], $config['password'], $options);
    }
    
    return $pdo;
}

// Rate limiting (simple implementation)
function checkRateLimit($identifier, $maxRequests = 100, $timeWindow = 3600) {
    if (!PRODUCTION_MODE) {
        return true; // Skip rate limiting in development
    }
    
    $cacheFile = __DIR__ . '/../cache/rate_limit_' . md5($identifier) . '.json';
    $now = time();
    
    if (file_exists($cacheFile)) {
        $data = json_decode(file_get_contents($cacheFile), true);
        
        // Clean old entries
        $data = array_filter($data, function($timestamp) use ($now, $timeWindow) {
            return ($now - $timestamp) < $timeWindow;
        });
        
        if (count($data) >= $maxRequests) {
            return false; // Rate limit exceeded
        }
        
        $data[] = $now;
    } else {
        $data = [$now];
    }
    
    // Ensure cache directory exists
    $cacheDir = dirname($cacheFile);
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }
    
    file_put_contents($cacheFile, json_encode($data));
    return true;
}

// Initialize production mode
if (PRODUCTION_MODE) {
    // Ensure logs directory exists
    $logsDir = __DIR__ . '/../logs';
    if (!is_dir($logsDir)) {
        mkdir($logsDir, 0755, true);
    }
    
    // Ensure cache directory exists
    $cacheDir = __DIR__ . '/../cache';
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }
}
?>


