<?php
/**
 * API Content Management
 * Validate and store content via API
 */

// Convert all PHP errors/notices to exceptions to avoid HTML leakage
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

// Disable display_errors to prevent HTML output
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Buffer output so we can return clean JSON
ob_start();

// Configure session before any output
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Lax');
session_name('STELLA_SESSION');

header('Content-Type: application/json');

// Check for required files before including them
$requiredFiles = [
    __DIR__ . '/../api/auth_helper.php',
    __DIR__ . '/../app/Models/GovernanceRule.php',
    __DIR__ . '/../app/Models/Asset.php',
    __DIR__ . '/../app/Models/BrandKit.php'
];

foreach ($requiredFiles as $file) {
    if (!file_exists($file)) {
        while (ob_get_level()) { ob_end_clean(); }
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Required file not found: ' . basename($file)
        ]);
        exit;
    }
}

require_once __DIR__ . '/../api/auth_helper.php';
require_once __DIR__ . '/../app/Models/GovernanceRule.php';
require_once __DIR__ . '/../app/Models/Asset.php';
require_once __DIR__ . '/../app/Models/BrandKit.php';

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key, X-User-ID');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get authenticated user ID from header (dashboard sends this)
$userId = $_SERVER['HTTP_X_USER_ID'] ?? null;

// Fallback to requireAuth() for API key authentication
if (!$userId) {
    $user = requireAuth();
    if (!$user) {
        while (ob_get_level()) { ob_end_clean(); }
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized - Please provide X-User-ID header or API key']);
        exit;
    }
    $userId = $user['id'];
} else {
    $userId = (int)$userId;
}

// Get database connection
try {
    $config = require __DIR__ . '/../config/database.php';
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

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);

        switch ($action) {
            case 'validate':
                // Validate content against governance rules
                if (empty($data['content'])) {
                    while (ob_get_level()) { ob_end_clean(); }
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Content is required'
                    ]);
                    exit;
                }

                $governanceModel = new GovernanceRule($pdo);
                $validation = $governanceModel->validateContent($userId, $data['content']);

                while (ob_get_level()) { ob_end_clean(); }
                echo json_encode([
                    'success' => true,
                    'validation' => $validation
                ]);
                break;

            case 'store':
                // Store content as an asset or in a brand kit
                if (empty($data['content'])) {
                    while (ob_get_level()) { ob_end_clean(); }
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Content is required'
                    ]);
                    exit;
                }

                // Optional: Validate before storing
                $skip_validation = $data['skip_validation'] ?? false;
                if (!$skip_validation) {
                    $governanceModel = new GovernanceRule($pdo);
                    $validation = $governanceModel->validateContent($userId, $data['content']);

                    if (!$validation['valid']) {
                        // Check if there are any "error" severity violations
                        $errors = array_filter($validation['violations'], function($v) {
                            return $v['severity'] === 'error';
                        });

                        if (!empty($errors)) {
                            while (ob_get_level()) { ob_end_clean(); }
                            echo json_encode([
                                'success' => false,
                                'message' => 'Content validation failed',
                                'validation' => $validation
                            ]);
                            exit;
                        }
                    }
                }

                // Determine storage type
                $storage_type = $data['storage_type'] ?? 'asset'; // 'asset' or 'brand_kit'
                $name = $data['name'] ?? 'Untitled Content';
                $description = $data['description'] ?? '';

                if ($storage_type === 'brand_kit') {
                    // Store in brand kit
                    if (empty($data['brand_kit_id'])) {
                        while (ob_get_level()) { ob_end_clean(); }
                        http_response_code(400);
                        echo json_encode([
                            'success' => false,
                            'message' => 'brand_kit_id is required for brand_kit storage'
                        ]);
                        exit;
                    }

                    // Verify brand kit ownership
                    $kitModel = new BrandKit($pdo);
                    $kit = $kitModel->getById($data['brand_kit_id']);
                    
                    if (!$kit || $kit['user_id'] != $userId) {
                        while (ob_get_level()) { ob_end_clean(); }
                        http_response_code(403);
                        echo json_encode([
                            'success' => false,
                            'message' => 'Brand kit not found or access denied'
                        ]);
                        exit;
                    }

                    // Create a text file asset
                    $filename = 'content_' . time() . '.txt';
                    $temp_path = sys_get_temp_dir() . '/' . $filename;
                    file_put_contents($temp_path, $data['content']);

                    // Create asset
                    $assetModel = new Asset($pdo);
                    $asset_id = $assetModel->create([
                        'user_id' => $userId,
                        'brand_kit_id' => $data['brand_kit_id'],
                        'name' => $name,
                        'description' => $description,
                        'type' => 'text',
                        'file_path' => $filename,
                        'file_size' => strlen($data['content']),
                        'mime_type' => 'text/plain'
                    ]);

                    unlink($temp_path);

                    while (ob_get_level()) { ob_end_clean(); }
                    echo json_encode([
                        'success' => true,
                        'message' => 'Content stored in brand kit',
                        'asset_id' => $asset_id,
                        'storage_type' => 'brand_kit'
                    ]);
                } else {
                    // Store as standalone asset
                    $filename = 'content_' . time() . '.txt';
                    $temp_path = sys_get_temp_dir() . '/' . $filename;
                    file_put_contents($temp_path, $data['content']);

                    $assetModel = new Asset($pdo);
                    $asset_id = $assetModel->create([
                        'user_id' => $userId,
                        'name' => $name,
                        'description' => $description,
                        'type' => 'text',
                        'file_path' => $filename,
                        'file_size' => strlen($data['content']),
                        'mime_type' => 'text/plain'
                    ]);

                    unlink($temp_path);

                    while (ob_get_level()) { ob_end_clean(); }
                    echo json_encode([
                        'success' => true,
                        'message' => 'Content stored as asset',
                        'asset_id' => $asset_id,
                        'storage_type' => 'asset'
                    ]);
                }
                break;

            case 'validate-and-store':
                // Combined: validate and store in one call
                if (empty($data['content'])) {
                    while (ob_get_level()) { ob_end_clean(); }
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Content is required'
                    ]);
                    exit;
                }

                // First validate
                $governanceModel = new GovernanceRule($pdo);
                $validation = $governanceModel->validateContent($userId, $data['content']);

                // Check for error-level violations
                $errors = array_filter($validation['violations'] ?? [], function($v) {
                    return $v['severity'] === 'error';
                });

                if (!empty($errors)) {
                    while (ob_get_level()) { ob_end_clean(); }
                    echo json_encode([
                        'success' => false,
                        'message' => 'Content validation failed with errors',
                        'validation' => $validation
                    ]);
                    exit;
                }

                // If validation passed or only warnings, store the content
                $storage_type = $data['storage_type'] ?? 'asset';
                $name = $data['name'] ?? 'Untitled Content';
                $description = $data['description'] ?? '';
                
                $filename = 'content_' . time() . '.txt';
                $temp_path = sys_get_temp_dir() . '/' . $filename;
                file_put_contents($temp_path, $data['content']);

                $assetModel = new Asset($pdo);
                
                $asset_data = [
                    'user_id' => $userId,
                    'name' => $name,
                    'description' => $description,
                    'type' => 'text',
                    'file_path' => $filename,
                    'file_size' => strlen($data['content']),
                    'mime_type' => 'text/plain'
                ];

                if ($storage_type === 'brand_kit' && !empty($data['brand_kit_id'])) {
                    $asset_data['brand_kit_id'] = $data['brand_kit_id'];
                }

                $asset_id = $assetModel->create($asset_data);
                unlink($temp_path);

                while (ob_get_level()) { ob_end_clean(); }
                echo json_encode([
                    'success' => true,
                    'message' => 'Content validated and stored',
                    'asset_id' => $asset_id,
                    'validation' => $validation
                ]);
                break;

            default:
                while (ob_get_level()) { ob_end_clean(); }
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid action. Use: validate, store, or validate-and-store'
                ]);
        }
    } else {
        while (ob_get_level()) { ob_end_clean(); }
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed'
        ]);
    }
} catch (Exception $e) {
    // Clean any output buffer
    while (ob_get_level()) { ob_end_clean(); }
    
    error_log("API Content Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
} finally {
    // Ensure clean output
    while (ob_get_level()) { ob_end_clean(); }
}

