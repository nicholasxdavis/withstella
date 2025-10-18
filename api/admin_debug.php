<?php
/**
 * Admin API Debug Version
 * This version catches ALL errors and always returns JSON
 */

// Set error handler to capture all errors
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

// Catch ALL output
ob_start();

try {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-User-ID, X-User-Email');

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        ob_end_clean();
        exit;
    }

    $debug = [
        'step' => 0,
        'error' => null,
        'trace' => []
    ];

    // Step 1: Check if config file exists
    $debug['step'] = 1;
    $debug['trace'][] = 'Checking for database config';
    $dbConfigPath = __DIR__ . '/../config/database.php';
    if (!file_exists($dbConfigPath)) {
        throw new Exception('Database config not found: ' . $dbConfigPath);
    }
    $debug['trace'][] = 'Database config file found';

    // Step 2: Load database config
    $debug['step'] = 2;
    $debug['trace'][] = 'Loading database config';
    require_once $dbConfigPath;
    $debug['trace'][] = 'Database config loaded';

    // Step 3: Check if function exists
    $debug['step'] = 3;
    $debug['trace'][] = 'Checking for getDBConnection function';
    if (!function_exists('getDBConnection')) {
        throw new Exception('getDBConnection function not found');
    }
    $debug['trace'][] = 'getDBConnection function exists';

    // Step 4: Get database connection
    $debug['step'] = 4;
    $debug['trace'][] = 'Getting database connection';
    $db = getDBConnection();
    $debug['trace'][] = 'Database connected';

    // Step 5: Check auth helper
    $debug['step'] = 5;
    $debug['trace'][] = 'Checking for auth helper';
    $authHelperPath = __DIR__ . '/../api/auth_helper.php';
    if (!file_exists($authHelperPath)) {
        throw new Exception('Auth helper not found: ' . $authHelperPath);
    }
    require_once $authHelperPath;
    $debug['trace'][] = 'Auth helper loaded';

    // Step 6: Check authentication
    $debug['step'] = 6;
    $debug['trace'][] = 'Checking authentication';
    $user = requireAuth();
    
    if (!$user || !isset($user['email'])) {
        ob_end_clean();
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Authentication required',
            'debug' => $debug
        ]);
        exit;
    }
    $debug['trace'][] = 'User authenticated: ' . $user['email'];

    // Step 7: Check admin privileges
    $debug['step'] = 7;
    if ($user['email'] !== 'nic@blacnova.net') {
        ob_end_clean();
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Admin privileges required',
            'debug' => $debug
        ]);
        exit;
    }
    $debug['trace'][] = 'Admin access granted';

    // Step 8: Handle request
    $debug['step'] = 8;
    $action = $_GET['action'] ?? 'none';
    $debug['trace'][] = 'Action: ' . $action;

    if ($action === 'stats') {
        $debug['step'] = 9;
        $debug['trace'][] = 'Getting stats';
        
        $stats = [
            'total_users' => 0,
            'total_assets' => 0,
            'total_kits' => 0,
            'total_api_keys' => 0,
            'recent_activity' => []
        ];
        
        try {
            $result = $db->query("SELECT COUNT(*) FROM users");
            $stats['total_users'] = (int)$result->fetchColumn();
        } catch (PDOException $e) {
            $debug['trace'][] = 'Error counting users: ' . $e->getMessage();
        }
        
        try {
            $result = $db->query("SELECT COUNT(*) FROM assets");
            $stats['total_assets'] = (int)$result->fetchColumn();
        } catch (PDOException $e) {
            $debug['trace'][] = 'Error counting assets: ' . $e->getMessage();
        }
        
        try {
            $result = $db->query("SELECT COUNT(*) FROM brand_kits");
            $stats['total_kits'] = (int)$result->fetchColumn();
        } catch (PDOException $e) {
            $debug['trace'][] = 'Error counting brand_kits: ' . $e->getMessage();
        }
        
        try {
            $result = $db->query("SELECT COUNT(*) FROM api_keys");
            $stats['total_api_keys'] = (int)$result->fetchColumn();
        } catch (PDOException $e) {
            $debug['trace'][] = 'Error counting api_keys: ' . $e->getMessage();
        }
        
        $debug['trace'][] = 'Stats retrieved successfully';
        
        ob_end_clean();
        echo json_encode([
            'success' => true,
            'stats' => $stats,
            'debug' => $debug
        ]);
        exit;
    }

    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Invalid action: ' . $action,
        'debug' => $debug
    ]);

} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
        'debug' => $debug
    ]);
}

