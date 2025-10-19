<?php
/**
 * Stella AI - Production Router
 * Production-ready router with security and performance optimizations
 */

// Load production configuration
require_once __DIR__ . '/config/production.php';

// Start session with secure settings
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
    ini_set('session.use_strict_mode', 1);
    session_name('STELLA_SESSION');
    session_start();
}

// Rate limiting
$clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (!checkRateLimit($clientIP, 1000, 3600)) { // 1000 requests per hour
    http_response_code(429);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Rate limit exceeded. Please try again later.']);
    exit;
}

// Simple routing
$request = $_SERVER['REQUEST_URI'];
$path = parse_url($request, PHP_URL_PATH);

// Remove leading slash
$path = ltrim($path, '/');

// Security: Block dangerous paths
$dangerousPaths = ['../', '..\\', 'config/', 'logs/', 'cache/', 'database/', 'app/'];
foreach ($dangerousPaths as $dangerous) {
    if (strpos($path, $dangerous) !== false) {
        http_response_code(403);
        echo 'Access denied';
        exit;
    }
}

// Route handling
switch ($path) {
    case '':
    case 'index.html':
        include 'index.html';
        break;
        
    case 'dashboard':
    case 'dashboard/':
        include 'dashboard/index.php';
        break;
        
    case 'admin':
    case 'admin.html':
        include 'admin.html';
        break;
        
    case 'api':
        // API requests are handled by individual API files
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'API endpoint not found']);
        break;
        
    case 'health':
    case 'health.php':
        include 'health.php';
        break;
        
    case 'install':
    case 'install.php':
        // Only allow install in development
        if (!PRODUCTION_MODE) {
            include 'install.php';
        } else {
            http_response_code(404);
            echo 'Not found';
        }
        break;
        
    default:
        // Check if it's an API request
        if (strpos($path, 'api/') === 0) {
            $apiFile = substr($path, 4); // Remove 'api/' prefix
            
            // Security: Validate API file name
            if (!preg_match('/^[a-zA-Z0-9_-]+\.php$/', $apiFile)) {
                http_response_code(400);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Invalid API endpoint']);
                exit;
            }
            
            $apiPath = "api/{$apiFile}";
            
            if (file_exists($apiPath)) {
                include $apiPath;
            } else {
                http_response_code(404);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'API endpoint not found']);
            }
        } else {
            // Try to serve the file directly (with security checks)
            $allowedExtensions = ['html', 'css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'ico', 'woff', 'woff2'];
            $extension = pathinfo($path, PATHINFO_EXTENSION);
            
            if (in_array(strtolower($extension), $allowedExtensions) && file_exists($path)) {
                // Set appropriate content type
                $contentTypes = [
                    'html' => 'text/html',
                    'css' => 'text/css',
                    'js' => 'application/javascript',
                    'png' => 'image/png',
                    'jpg' => 'image/jpeg',
                    'jpeg' => 'image/jpeg',
                    'gif' => 'image/gif',
                    'svg' => 'image/svg+xml',
                    'ico' => 'image/x-icon',
                    'woff' => 'font/woff',
                    'woff2' => 'font/woff2'
                ];
                
                if (isset($contentTypes[$extension])) {
                    header('Content-Type: ' . $contentTypes[$extension]);
                }
                
                include $path;
            } else {
                http_response_code(404);
                echo 'Page not found';
            }
        }
        break;
}