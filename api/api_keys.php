<?php
/**
 * API Keys Management
 * Create, list, and delete API keys
 */

// Convert all PHP errors to exceptions and buffer output to ensure JSON-only responses
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});
ob_start();

// Configure session before any output
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Lax');
session_name('STELLA_SESSION');

header('Content-Type: application/json');
require_once __DIR__ . '/../api/auth_helper.php';
require_once __DIR__ . '/../api/check_pro.php';
require_once __DIR__ . '/../app/Models/ApiKey.php';

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-User-ID, X-User-Email');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    while (ob_get_level()) { ob_end_clean(); }
    http_response_code(200);
    exit;
}

try {
    // Resolve authenticated user (supports X-User-ID, X-User-Email, API key, or session)
    $user = requireAuth();
    if (!$user) {
        while (ob_get_level()) { ob_end_clean(); }
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    $userId = (int)$user['id'];

// Get database connection
    if (!isset($GLOBALS['dbConfig'])) { require __DIR__ . '/../config/database.php'; }
    $config = $GLOBALS['dbConfig'];
    $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset=utf8mb4";
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (Exception $e) {
    while (ob_get_level()) { ob_end_clean(); }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $e->getMessage()
    ]);
    exit;
}

// Ensure the authenticated user exists; fallback to admin email if provided
try {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $exists = $stmt->fetchColumn();
    if (!$exists) {
        $headerEmail = $_SERVER['HTTP_X_USER_EMAIL'] ?? '';
        if (!empty($headerEmail) && strtolower($headerEmail) === 'nic@blacnova.net') {
            // Try to find by email first
            $eStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $eStmt->execute([$headerEmail]);
            $foundId = $eStmt->fetchColumn();
            if ($foundId) {
                $userId = (int)$foundId;
            } else {
                // Create minimal admin user
                $randomPassword = bin2hex(random_bytes(16));
                $hashedPassword = password_hash($randomPassword, PASSWORD_BCRYPT);
                $cStmt = $pdo->prepare("INSERT INTO users (full_name, email, password, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
                $cStmt->execute(['Admin', $headerEmail, $hashedPassword]);
                $userId = (int)$pdo->lastInsertId();
            }
        } else {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid user. Please log in again.'
            ]);
            exit;
        }
    }
} catch (Exception $e) {
    while (ob_get_level()) { ob_end_clean(); }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'User validation failed: ' . $e->getMessage()
    ]);
    exit;
}

// 🔒 PRO PLAN REQUIRED - API Keys are a Pro feature
requireProPlan($pdo, $userId);

try {
    $apiKeyModel = new ApiKey($pdo);
} catch (Exception $e) {
    while (ob_get_level()) { ob_end_clean(); }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to initialize: ' . $e->getMessage(),
        'hint' => 'Run: php database/migrate.php to create tables'
    ]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // List all API keys for the user
            $keys = $apiKeyModel->getByUser($userId);
            echo json_encode([
                'success' => true,
                'keys' => $keys
            ]);
            break;

        case 'POST':
            // Create a new API key
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['name'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'API key name is required'
                ]);
                exit;
            }

            $result = $apiKeyModel->create($userId, $data['name']);
            
            echo json_encode([
                'success' => true,
                'message' => 'API key created successfully',
                'key' => $result
            ]);
            break;

        case 'DELETE':
            // Delete an API key
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'API key ID required'
                ]);
                exit;
            }

            $success = $apiKeyModel->delete($_GET['id'], $userId);
            
            if ($success) {
                echo json_encode([
                    'success' => true,
                    'message' => 'API key deleted successfully'
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'API key not found'
                ]);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'message' => 'Method not allowed'
            ]);
    }
} catch (Throwable $e) {
    while (ob_get_level()) { ob_end_clean(); }
    error_log("API Keys Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

