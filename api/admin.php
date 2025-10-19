<?php
/**
 * Admin API - Restricted to nic@blacnova.net only
 * Rebuilt with comprehensive error handling
 */

// Error handling setup - ensure ALL errors are caught
@ini_set('display_errors', 0);
@error_reporting(E_ALL);
@ini_set('log_errors', 1);

// Convert all errors to exceptions
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

// Start output buffering to catch any errors
ob_start();

// Set JSON headers first
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-User-ID, X-User-Email');

// Handle OPTIONS for CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    while (ob_get_level()) {
        ob_end_clean();
    }
    http_response_code(200);
    exit;
}

/**
 * Send JSON response and exit
 */
function sendResponse($data, $statusCode = 200) {
    // Clean all output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }
    http_response_code($statusCode);
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

/**
 * Send error response
 */
function sendError($message, $statusCode = 500, $details = []) {
    $response = [
        'success' => false,
        'message' => $message
    ];
    
    if (!empty($details)) {
        $response['details'] = $details;
    }
    
    error_log("Admin API Error: $message");
    sendResponse($response, $statusCode);
}

try {
    // Load required files
    $authHelperPath = __DIR__ . '/../api/auth_helper.php';
    $dbConfigPath = __DIR__ . '/../config/database.php';
    
    if (!file_exists($authHelperPath)) {
        sendError('auth_helper.php not found at: ' . $authHelperPath, 500);
    }
    
    if (!file_exists($dbConfigPath)) {
        sendError('database.php not found at: ' . $dbConfigPath, 500);
    }
    
    require_once $authHelperPath;
    require_once $dbConfigPath;
    
    // Authenticate user
    $user = requireAuth();
    
    if (!$user || !isset($user['email'])) {
        sendError('Authentication required. Please log in.', 401);
    }
    
    // ADMIN CHECK - Only nic@blacnova.net can access
    if ($user['email'] !== 'nic@blacnova.net') {
        sendError('Access denied. Admin privileges required.', 403);
    }
    
    // Get database connection
    try {
        $db = getDBConnection();
        if (!$db) {
            sendError('Failed to get database connection', 500);
        }
    } catch (Exception $e) {
        sendError('Database connection failed: ' . $e->getMessage(), 500);
    }
    
    // Handle GET requests
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'stats':
                try {
                    $stats = [
                        'total_users' => 0,
                        'total_assets' => 0,
                        'total_kits' => 0,
                        'total_api_keys' => 0,
                        'recent_activity' => []
                    ];
                    
                    // Get counts with error handling
                    try {
                        $result = $db->query("SELECT COUNT(*) FROM users");
                        $stats['total_users'] = (int)$result->fetchColumn();
                    } catch (PDOException $e) {
                        error_log("Error counting users: " . $e->getMessage());
                    }
                    
                    try {
                        $result = $db->query("SELECT COUNT(*) FROM assets");
                        $stats['total_assets'] = (int)$result->fetchColumn();
                    } catch (PDOException $e) {
                        error_log("Error counting assets: " . $e->getMessage());
                    }
                    
                    try {
                        $result = $db->query("SELECT COUNT(*) FROM brand_kits");
                        $stats['total_kits'] = (int)$result->fetchColumn();
                    } catch (PDOException $e) {
                        error_log("Error counting brand_kits: " . $e->getMessage());
                    }
                    
                    try {
                        $result = $db->query("SELECT COUNT(*) FROM api_keys");
                        $stats['total_api_keys'] = (int)$result->fetchColumn();
                    } catch (PDOException $e) {
                        error_log("Error counting api_keys: " . $e->getMessage());
                    }
                    
                    // Get recent activity
                    try {
                        $activityStmt = $db->query("
                            SELECT a.*, u.email as user_email 
                            FROM activities a 
                            LEFT JOIN users u ON a.user_id = u.id 
                            ORDER BY a.created_at DESC 
                            LIMIT 20
                        ");
                        $stats['recent_activity'] = $activityStmt->fetchAll(PDO::FETCH_ASSOC);
                    } catch (PDOException $e) {
                        error_log("Error fetching activities: " . $e->getMessage());
                    }
                    
                    sendResponse([
                        'success' => true,
                        'stats' => $stats
                    ]);
                } catch (Exception $e) {
                    sendError('Failed to load stats: ' . $e->getMessage(), 500);
                }
                break;
                
            case 'users':
                try {
                    $stmt = $db->query("
                        SELECT 
                            u.*,
                            (SELECT COUNT(*) FROM assets WHERE user_id = u.id) as assets_count,
                            (SELECT COUNT(*) FROM brand_kits WHERE user_id = u.id) as kits_count,
                            (SELECT COUNT(*) FROM team_members WHERE workspace_owner_id = u.id) as team_size
                        FROM users u
                        ORDER BY u.created_at DESC
                    ");
                    
                    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    sendResponse([
                        'success' => true,
                        'users' => $users
                    ]);
                } catch (PDOException $e) {
                    sendError('Failed to load users: ' . $e->getMessage(), 500);
                }
                break;
                
            case 'content':
                try {
                    $assets = [];
                    $kits = [];
                    $rules = [];
                    
                    // Get assets
                    try {
                        $assetsStmt = $db->query("
                            SELECT a.*, u.email as user_email, u.full_name as user_name
                            FROM assets a 
                            LEFT JOIN users u ON a.user_id = u.id 
                            ORDER BY a.created_at DESC 
                            LIMIT 50
                        ");
                        $assets = $assetsStmt->fetchAll(PDO::FETCH_ASSOC);
                    } catch (PDOException $e) {
                        error_log("Error fetching assets: " . $e->getMessage());
                    }
                    
                    // Get brand kits
                    try {
                        $kitsStmt = $db->query("
                            SELECT 
                                bk.*,
                                u.email as user_email,
                                u.full_name as user_name,
                                (SELECT COUNT(*) FROM assets WHERE brand_kit_id = bk.id) as assets_count
                            FROM brand_kits bk 
                            LEFT JOIN users u ON bk.user_id = u.id 
                            ORDER BY bk.created_at DESC 
                            LIMIT 50
                        ");
                        $kits = $kitsStmt->fetchAll(PDO::FETCH_ASSOC);
                    } catch (PDOException $e) {
                        error_log("Error fetching brand_kits: " . $e->getMessage());
                    }
                    
                    // Get governance rules
                    try {
                        $rulesStmt = $db->query("
                            SELECT gr.*, u.email as user_email, u.full_name as user_name
                            FROM governance_rules gr 
                            LEFT JOIN users u ON gr.user_id = u.id 
                            ORDER BY gr.created_at DESC
                        ");
                        $rules = $rulesStmt->fetchAll(PDO::FETCH_ASSOC);
                    } catch (PDOException $e) {
                        error_log("Error fetching governance_rules: " . $e->getMessage());
                    }
                    
                    sendResponse([
                        'success' => true,
                        'content' => [
                            'assets' => $assets,
                            'kits' => $kits,
                            'rules' => $rules
                        ]
                    ]);
                } catch (Exception $e) {
                    sendError('Failed to load content: ' . $e->getMessage(), 500);
                }
                break;
                
            case 'system':
                try {
                    $tables = [
                        'users', 'assets', 'brand_kits', 'team_members', 
                        'pending_invites', 'activities', 'governance_rules', 
                        'api_keys', 'analytics_events'
                    ];
                    
                    $tableInfo = [];
                    foreach ($tables as $table) {
                        try {
                            $count = $db->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
                            $tableInfo[] = [
                                'name' => $table,
                                'exists' => true,
                                'rows' => (int)$count
                            ];
                        } catch (PDOException $e) {
                            $tableInfo[] = [
                                'name' => $table,
                                'exists' => false,
                                'rows' => 0,
                                'error' => $e->getMessage()
                            ];
                        }
                    }
                    
                    $dbVersion = 'Unknown';
                    try {
                        $dbVersion = $db->query("SELECT VERSION()")->fetchColumn();
                    } catch (PDOException $e) {
                        error_log("Error getting DB version: " . $e->getMessage());
                    }
                    
                    sendResponse([
                        'success' => true,
                        'system' => [
                            'tables' => $tableInfo,
                            'php_version' => phpversion(),
                            'db_version' => $dbVersion,
                            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'
                        ]
                    ]);
                } catch (Exception $e) {
                    sendError('Failed to load system info: ' . $e->getMessage(), 500);
                }
                break;
                
            case 'export':
                try {
                    $export = [
                        'users' => $db->query("SELECT * FROM users")->fetchAll(PDO::FETCH_ASSOC),
                        'assets' => $db->query("SELECT * FROM assets")->fetchAll(PDO::FETCH_ASSOC),
                        'brand_kits' => $db->query("SELECT * FROM brand_kits")->fetchAll(PDO::FETCH_ASSOC),
                        'activities' => $db->query("SELECT * FROM activities")->fetchAll(PDO::FETCH_ASSOC),
                        'governance_rules' => $db->query("SELECT * FROM governance_rules")->fetchAll(PDO::FETCH_ASSOC),
                        'api_keys' => $db->query("SELECT * FROM api_keys")->fetchAll(PDO::FETCH_ASSOC)
                    ];
                    
                    ob_end_clean();
                    header('Content-Disposition: attachment; filename="stella-export-' . date('Y-m-d') . '.json"');
                    echo json_encode($export, JSON_PRETTY_PRINT);
                    exit;
                } catch (Exception $e) {
                    sendError('Failed to export data: ' . $e->getMessage(), 500);
                }
                break;
                
            default:
                sendError('Invalid action: ' . $action, 400);
        }
    }
    
    // Handle POST requests
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        
        switch ($action) {
            case 'delete_user':
                $userId = $input['user_id'] ?? null;
                
                if (!$userId) {
                    sendError('User ID required', 400);
                }
                
                try {
                    // Prevent deleting admin
                    $userStmt = $db->prepare("SELECT email FROM users WHERE id = ?");
                    $userStmt->execute([$userId]);
                    $targetUser = $userStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$targetUser) {
                        sendError('User not found', 404);
                    }
                    
                    if ($targetUser['email'] === 'nic@blacnova.net') {
                        sendError('Cannot delete admin user', 403);
                    }
                    
                    // Delete user and all related data
                    $db->beginTransaction();
                    
                    try {
                        // Manual cleanup for tables without cascade
                        $db->prepare("DELETE FROM activities WHERE user_id = ?")->execute([$userId]);
                        $db->prepare("DELETE FROM governance_rules WHERE user_id = ?")->execute([$userId]);
                        $db->prepare("DELETE FROM api_keys WHERE user_id = ?")->execute([$userId]);
                        $db->prepare("DELETE FROM team_members WHERE workspace_owner_id = ?")->execute([$userId]);
                        
                        // Delete user (cascading will handle assets, kits, etc.)
                        $db->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]);
                        
                        $db->commit();
                        
                        sendResponse([
                            'success' => true,
                            'message' => 'User deleted successfully'
                        ]);
                    } catch (Exception $e) {
                        $db->rollBack();
                        throw $e;
                    }
                } catch (Exception $e) {
                    sendError('Failed to delete user: ' . $e->getMessage(), 500);
                }
                break;
                
            case 'send_password_reset':
                $userId = $input['user_id'] ?? null;
                
                if (!$userId) {
                    sendError('User ID required', 400);
                }
                
                try {
                    // Get user email
                    $userStmt = $db->prepare("SELECT email, full_name FROM users WHERE id = ?");
                    $userStmt->execute([$userId]);
                    $targetUser = $userStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$targetUser) {
                        sendError('User not found', 404);
                    }
                    
                    // Generate reset token
                    $token = bin2hex(random_bytes(32));
                    $stmt = $db->prepare("
                        INSERT INTO password_reset_tokens (email, token, created_at) 
                        VALUES (?, ?, NOW())
                        ON DUPLICATE KEY UPDATE token = ?, created_at = NOW()
                    ");
                    $stmt->execute([$targetUser['email'], $token, $token]);
                    
                    // Generate reset link
                    $resetLink = "http://" . $_SERVER['HTTP_HOST'] . "/public/reset-password.php?token=" . $token;
                    
                    sendResponse([
                        'success' => true,
                        'message' => 'Password reset link generated',
                        'reset_link' => $resetLink,
                        'note' => 'In production, this would be sent via email'
                    ]);
                } catch (Exception $e) {
                    sendError('Failed to generate password reset: ' . $e->getMessage(), 500);
                }
                break;
                
            default:
                sendError('Invalid action: ' . $action, 400);
        }
    }
    
    // Invalid method
    sendError('Invalid request method: ' . $_SERVER['REQUEST_METHOD'], 405);
    
} catch (Throwable $e) {
    error_log("Admin API Fatal Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Clean any output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error occurred: ' . $e->getMessage(),
        'details' => [
            'error' => $e->getMessage(),
            'file' => basename($e->getFile()),
            'line' => $e->getLine()
        ]
    ], JSON_PRETTY_PRINT);
    exit;
}
