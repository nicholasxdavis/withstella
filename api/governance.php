<?php
/**
 * Governance API
 * Manage brand governance rules
 */

// Convert all PHP errors/notices to exceptions to avoid HTML leakage
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

// Buffer output so we can return clean JSON
ob_start();

// Configure session before any output
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Lax');
session_name('STELLA_SESSION');

header('Content-Type: application/json');
require_once __DIR__ . '/../api/auth_helper.php';
require_once __DIR__ . '/../api/check_pro.php';
require_once __DIR__ . '/../app/Models/GovernanceRule.php';

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-User-ID, X-User-Email');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    while (ob_get_level()) { ob_end_clean(); }
    http_response_code(200);
    exit;
}

try {
    // Resolve authenticated user using helper (supports X-User-ID, X-User-Email, API key, session)
    $user = requireAuth();
    if (!$user) {
        while (ob_get_level()) { ob_end_clean(); }
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    $userId = (int)$user['id'];
    // Determine role for permission checks
    $userRole = function_exists('getUserRole') ? getUserRole($userId) : 'Owner';

    // Get database connection
    if (!isset($GLOBALS['dbConfig'])) { require __DIR__ . '/../config/database.php'; }
    $config = $GLOBALS['dbConfig'];
    $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset=utf8mb4";
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    $governanceModel = new GovernanceRule($pdo);

    // 🔒 PRO PLAN REQUIRED - Governance is a Pro feature
    requireProPlan($pdo, $userId);

    $method = $_SERVER['REQUEST_METHOD'];
    $path = $_GET['path'] ?? '';

    switch ($method) {
        case 'GET':
            if ($path === 'list' || empty($path)) {
                // Get all rules for the user
                $rules = $governanceModel->getByUser($userId);
                while (ob_get_level()) { ob_end_clean(); }
                echo json_encode([
                    'success' => true,
                    'rules' => $rules
                ]);
            } elseif (isset($_GET['id'])) {
                // Get a specific rule
                $rule = $governanceModel->getById($_GET['id'], $userId);
                if ($rule) {
                    while (ob_get_level()) { ob_end_clean(); }
                    echo json_encode([
                        'success' => true,
                        'rule' => $rule
                    ]);
                } else {
                    while (ob_get_level()) { ob_end_clean(); }
                    http_response_code(404);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Rule not found'
                    ]);
                }
            } else {
                while (ob_get_level()) { ob_end_clean(); }
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid request'
                ]);
            }
            break;

        case 'POST':
            // Only Owner/Admin can create rules
            if (!in_array($userRole, ['Owner', 'Admin'])) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Permission denied']);
                break;
            }
            // Create a new rule
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validate required fields
            if (empty($data['name']) || empty($data['rule_type']) || empty($data['rule_value'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Missing required fields: name, rule_type, rule_value'
                ]);
                exit;
            }

            // Validate rule_type
            $valid_types = ['tone', 'forbidden_words', 'required_words', 'max_length', 'min_length', 'regex_pattern'];
            if (!in_array($data['rule_type'], $valid_types)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid rule_type. Must be one of: ' . implode(', ', $valid_types)
                ]);
                exit;
            }

            // Validate severity
            if (isset($data['severity']) && !in_array($data['severity'], ['warning', 'error'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid severity. Must be "warning" or "error"'
                ]);
                exit;
            }

            // Enforce name length
            if (strlen($data['name']) > 255) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Name too long (max 255 characters)'
                ]);
                exit;
            }

            // If provided, cap description length (non-breaking; DB can handle TEXT)
            if (!empty($data['description']) && strlen($data['description']) > 5000) {
                $data['description'] = substr($data['description'], 0, 5000);
            }

            // Normalize enabled flag if included
            if (isset($data['enabled'])) {
                $data['enabled'] = filter_var($data['enabled'], FILTER_VALIDATE_BOOLEAN);
            }

            // Validate rule_value by type (defensive, frontend formats many already)
            $rt = $data['rule_type'];
            $rv = $data['rule_value'];
            $rvValid = true; $rvError = '';
            switch ($rt) {
                case 'max_length':
                case 'min_length':
                    if (!is_numeric($rv) || (int)$rv <= 0) {
                        $rvValid = false; $rvError = 'Value must be a positive number for ' . $rt;
                    } else {
                        $data['rule_value'] = (string)intval($rv);
                    }
                    break;
                case 'regex_pattern':
                    // Suppress errors from invalid regex
                    set_error_handler(function(){}); $ok = @preg_match($rv, ''); restore_error_handler();
                    if ($ok === false) { $rvValid = false; $rvError = 'Invalid regex pattern'; }
                    break;
                case 'forbidden_words':
                case 'required_words':
                    // Accept JSON array or comma-separated
                    $asArray = json_decode($rv, true);
                    if (!is_array($asArray)) {
                        $asArray = array_filter(array_map('trim', explode(',', (string)$rv)), function($w){ return $w !== ''; });
                    }
                    $data['rule_value'] = json_encode(array_values($asArray));
                    break;
                case 'tone':
                    $tone = json_decode($rv, true);
                    if (!is_array($tone) || !isset($tone['type'])) {
                        // Accept simple comma list as avoid-list defaulting to positive tone
                        $avoid = array_filter(array_map('trim', explode(',', (string)$rv)), function($w){ return $w !== ''; });
                        $tone = ['type' => 'positive', 'avoid' => array_values($avoid)];
                    }
                    $data['rule_value'] = json_encode($tone);
                    break;
            }
            if (!$rvValid) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => $rvError]);
                exit;
            }

            $rule_id = $governanceModel->create($userId, $data);
            
            echo json_encode([
                'success' => true,
                'message' => 'Rule created successfully',
                'rule_id' => $rule_id
            ]);
            break;

        case 'PUT':
            // Only Owner/Admin can update rules
            if (!in_array($userRole, ['Owner', 'Admin'])) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Permission denied']);
                break;
            }
            // Update a rule
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Rule ID required'
                ]);
                exit;
            }

            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validate rule_type if provided
            if (isset($data['rule_type'])) {
                $valid_types = ['tone', 'forbidden_words', 'required_words', 'max_length', 'min_length', 'regex_pattern'];
                if (!in_array($data['rule_type'], $valid_types)) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Invalid rule_type'
                    ]);
                    exit;
                }
            }

            // Validate severity if provided
            if (isset($data['severity']) && !in_array($data['severity'], ['warning', 'error'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid severity'
                ]);
                exit;
            }

            // Normalize enabled flag
            if (isset($data['enabled'])) {
                $data['enabled'] = filter_var($data['enabled'], FILTER_VALIDATE_BOOLEAN);
            }

            // If rule_value provided, validate against (new or existing) type
            if (array_key_exists('rule_value', $data)) {
                // Get existing rule to determine type if not provided
                $targetType = $data['rule_type'] ?? null;
                if (!$targetType) {
                    $tStmt = $pdo->prepare("SELECT rule_type FROM governance_rules WHERE id = ? AND user_id = ?");
                    $tStmt->execute([$_GET['id'], $userId]);
                    $row = $tStmt->fetch();
                    $targetType = $row['rule_type'] ?? null;
                }
                if ($targetType) {
                    $rv = $data['rule_value'];
                    $rvValid = true; $rvError = '';
                    switch ($targetType) {
                        case 'max_length':
                        case 'min_length':
                            if (!is_numeric($rv) || (int)$rv <= 0) { $rvValid = false; $rvError = 'Value must be a positive number'; }
                            else { $data['rule_value'] = (string)intval($rv); }
                            break;
                        case 'regex_pattern':
                            set_error_handler(function(){}); $ok = @preg_match($rv, ''); restore_error_handler();
                            if ($ok === false) { $rvValid = false; $rvError = 'Invalid regex pattern'; }
                            break;
                        case 'forbidden_words':
                        case 'required_words':
                            $asArray = json_decode($rv, true);
                            if (!is_array($asArray)) {
                                $asArray = array_filter(array_map('trim', explode(',', (string)$rv)), function($w){ return $w !== ''; });
                            }
                            $data['rule_value'] = json_encode(array_values($asArray));
                            break;
                        case 'tone':
                            $tone = json_decode($rv, true);
                            if (!is_array($tone) || !isset($tone['type'])) {
                                $avoid = array_filter(array_map('trim', explode(',', (string)$rv)), function($w){ return $w !== ''; });
                                $tone = ['type' => 'positive', 'avoid' => array_values($avoid)];
                            }
                            $data['rule_value'] = json_encode($tone);
                            break;
                    }
                    if (!$rvValid) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => $rvError]);
                        exit;
                    }
                }
            }

            $success = $governanceModel->update($_GET['id'], $userId, $data);
            
            if ($success) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Rule updated successfully'
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Rule not found or no changes made'
                ]);
            }
            break;

        case 'DELETE':
            // Only Owner/Admin can delete rules
            if (!in_array($userRole, ['Owner', 'Admin'])) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Permission denied']);
                break;
            }
            // Delete a rule
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Rule ID required'
                ]);
                exit;
            }

            $success = $governanceModel->delete($_GET['id'], $userId);
            
            if ($success) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Rule deleted successfully'
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Rule not found'
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
    // Flush buffered JSON to client; log stray output if needed
    if (ob_get_length()) {
        // Peek without discarding
        $peek = ob_get_contents();
        if ($peek && strpos($peek, '{') !== 0 && strpos($peek, '[') !== 0) {
            error_log('Governance buffered output (non-JSON): ' . substr($peek, 0, 500));
        }
        ob_end_flush();
    }
} catch (Throwable $e) {
    while (ob_get_level()) { ob_end_clean(); }
    error_log("Governance API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

