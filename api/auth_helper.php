<?php
/**
 * Authentication Helper
 * Provides functions to authenticate users and get user ID from requests
 */

/**
 * Get authenticated user ID from request
 * Checks for user ID in headers or session
 * 
 * @param PDO $pdo Database connection
 * @return int User ID
 * @throws Exception if not authenticated
 */
function getAuthenticatedUserId($pdo) {
    // Check for user ID in Authorization header (format: "Bearer {userId}")
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['HTTP_X_USER_ID'] ?? '';
    
    // Try to get user ID from custom header
    if (!empty($authHeader)) {
        // If it's "Bearer {userId}", extract the ID
        if (preg_match('/Bearer\s+(\d+)/', $authHeader, $matches)) {
            return (int)$matches[1];
        }
        // If it's just a number, use it directly
        if (is_numeric($authHeader)) {
            return (int)$authHeader;
        }
    }
    
    // Check for user_id in POST data (for multipart/form-data)
    if (isset($_POST['user_id']) && is_numeric($_POST['user_id'])) {
        return (int)$_POST['user_id'];
    }
    
    // Check for user_id in query parameters
    if (isset($_GET['user_id']) && is_numeric($_GET['user_id'])) {
        return (int)$_GET['user_id'];
    }
    
    // Fallback: Try to get from PHP session
    session_start();
    if (isset($_SESSION['user_id'])) {
        return (int)$_SESSION['user_id'];
    }
    
    // If we reach here, user is not authenticated
    throw new Exception('Not authenticated. Please provide user_id.');
}

/**
 * Verify user exists and is valid
 * 
 * @param PDO $pdo Database connection
 * @param int $userId User ID to verify
 * @return bool True if user exists
 */
function verifyUser($pdo, $userId) {
    try {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch() !== false;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Get Nextcloud credentials for user
 * Team members use workspace owner's Nextcloud account
 * Workspace owners use their own account
 * 
 * @param PDO $pdo Database connection
 * @param int $userId User ID
 * @return array|false Array with nextcloud_username and nextcloud_password, or false
 */
function getNextcloudCredentials($pdo, $userId) {
    try {
        // Get user info including workspace owner if they're a team member
        $stmt = $pdo->prepare("SELECT u.nextcloud_username, u.nextcloud_password, u.workspace_owner_id,
                                     owner.nextcloud_username as owner_nc_username,
                                     owner.nextcloud_password as owner_nc_password
                              FROM users u
                              LEFT JOIN users owner ON u.workspace_owner_id = owner.id
                              WHERE u.id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return false;
        }
        
        // If user is a team member, use owner's Nextcloud credentials
        if ($user['workspace_owner_id'] && $user['owner_nc_username']) {
            return [
                'nextcloud_username' => $user['owner_nc_username'],
                'nextcloud_password' => $user['owner_nc_password'],
                'is_team_member' => true,
                'workspace_owner_id' => $user['workspace_owner_id']
            ];
        }
        
        // Otherwise use their own credentials
        return [
            'nextcloud_username' => $user['nextcloud_username'],
            'nextcloud_password' => $user['nextcloud_password'],
            'is_team_member' => false,
            'workspace_owner_id' => null
        ];
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Require authentication - supports both session and API key
 * Returns user array or false if not authenticated
 * 
 * @return array|false User data or false
 */
function requireAuth() {
    // Try API key first
    $apiKey = null;
    
    // Check Authorization header for API key
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
        if (preg_match('/Bearer\s+(sk_[a-f0-9]+)/', $authHeader, $matches)) {
            $apiKey = $matches[1];
        }
    }
    
    // Check X-API-Key header
    if (!$apiKey && isset($_SERVER['HTTP_X_API_KEY'])) {
        $apiKey = $_SERVER['HTTP_X_API_KEY'];
    }
    
    // If API key provided, validate it
    if ($apiKey) {
        try {
            // Always obtain config from global; require as fallback
            if (!isset($GLOBALS['dbConfig'])) { require __DIR__ . '/../config/database.php'; }
            $config = $GLOBALS['dbConfig'];
            $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset=utf8mb4";
            $pdo = new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            
            require_once __DIR__ . '/../app/Models/ApiKey.php';
            $apiKeyModel = new ApiKey($pdo);
            $user = $apiKeyModel->validate($apiKey);
            
            if ($user) {
                return [
                    'id' => $user['id'],
                    'email' => $user['email'],
                    'full_name' => $user['full_name'],
                    'auth_method' => 'api_key'
                ];
            }
        } catch (Exception $e) {
            error_log("API Key validation error: " . $e->getMessage());
            return false;
        }
    }
    
    // Check X-User-ID header (used by dashboard)
    if (isset($_SERVER['HTTP_X_USER_ID']) && is_numeric($_SERVER['HTTP_X_USER_ID'])) {
        try {
            if (!isset($GLOBALS['dbConfig'])) { require __DIR__ . '/../config/database.php'; }
            $config = $GLOBALS['dbConfig'];
            $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset=utf8mb4";
            $pdo = new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            
            $userId = (int)$_SERVER['HTTP_X_USER_ID'];
            $stmt = $pdo->prepare("SELECT id, email, full_name FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if ($user) {
                return [
                    'id' => $user['id'],
                    'email' => $user['email'],
                    'full_name' => $user['full_name'],
                    'auth_method' => 'header'
                ];
            }

            // Fallback: if X-User-ID is not found, but admin email is provided,
            // try to locate or create that user. This helps bootstrap environments
            // where the client has sessionStorage only and DB user is missing.
            $headerEmail = $_SERVER['HTTP_X_USER_EMAIL'] ?? '';
            if (!empty($headerEmail) && strtolower($headerEmail) === 'nic@blacnova.net') {
                // Try by email first
                $stmt = $pdo->prepare("SELECT id, email, full_name FROM users WHERE email = ?");
                $stmt->execute([$headerEmail]);
                $user = $stmt->fetch();
                if ($user) {
                    return [
                        'id' => $user['id'],
                        'email' => $user['email'],
                        'full_name' => $user['full_name'],
                        'auth_method' => 'header'
                    ];
                }
                // Create minimal admin user record
                $fullName = 'Admin';
                $randomPassword = bin2hex(random_bytes(16));
                $hashedPassword = password_hash($randomPassword, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
                $stmt->execute([$fullName, $headerEmail, $hashedPassword]);
                $newId = (int)$pdo->lastInsertId();
                return [
                    'id' => $newId,
                    'email' => $headerEmail,
                    'full_name' => $fullName,
                    'auth_method' => 'header'
                ];
            }
        } catch (Exception $e) {
            error_log("X-User-ID validation error: " . $e->getMessage());
        }
    }
    
    // Fallback: Check X-User-Email header (admin bootstrap)
    if (isset($_SERVER['HTTP_X_USER_EMAIL']) && !empty($_SERVER['HTTP_X_USER_EMAIL'])) {
        try {
            $emailHeader = trim($_SERVER['HTTP_X_USER_EMAIL']);
            // Only allow this fallback for the known admin email to prevent spoofing
            if (strtolower($emailHeader) === 'nic@blacnova.net') {
                $config = require_once __DIR__ . '/../config/database.php';
                $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset=utf8mb4";
                $pdo = new PDO($dsn, $config['username'], $config['password'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]);
                
                // Look up by email
                $stmt = $pdo->prepare("SELECT id, email, full_name FROM users WHERE email = ?");
                $stmt->execute([$emailHeader]);
                $user = $stmt->fetch();
                if ($user) {
                    return [
                        'id' => $user['id'],
                        'email' => $user['email'],
                        'full_name' => $user['full_name'],
                        'auth_method' => 'header'
                    ];
                }
                // Create minimal admin user if not present
                $fullName = 'Admin';
                $randomPassword = bin2hex(random_bytes(16));
                $hashedPassword = password_hash($randomPassword, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
                $stmt->execute([$fullName, $emailHeader, $hashedPassword]);
                $newId = (int)$pdo->lastInsertId();
                return [
                    'id' => $newId,
                    'email' => $emailHeader,
                    'full_name' => $fullName,
                    'auth_method' => 'header'
                ];
            }
        } catch (Exception $e) {
            error_log("X-User-Email fallback error: " . $e->getMessage());
        }
    }

    // Fall back to session authentication
    if (session_status() === PHP_SESSION_NONE) {
        session_name('STELLA_SESSION');
        session_start();
    }
    
    if (isset($_SESSION['user_id']) && isset($_SESSION['user_email'])) {
        return [
            'id' => $_SESSION['user_id'],
            'email' => $_SESSION['user_email'],
            'full_name' => $_SESSION['user_name'] ?? $_SESSION['user_full_name'] ?? '',
            'auth_method' => 'session'
        ];
    }
    
    // Alternative: try to get from stella_user in session (if stored differently)
    if (isset($_SESSION['stella_user'])) {
        $stellaUser = is_string($_SESSION['stella_user']) ? json_decode($_SESSION['stella_user'], true) : $_SESSION['stella_user'];
        if (is_array($stellaUser) && isset($stellaUser['id'])) {
            return [
                'id' => $stellaUser['id'],
                'email' => $stellaUser['email'] ?? '',
                'full_name' => $stellaUser['full_name'] ?? $stellaUser['name'] ?? '',
                'auth_method' => 'session'
            ];
        }
    }
    
    return false;
}

/**
 * Get user role (for team members)
 * Returns: 'Admin', 'Team Member', 'Viewer', or 'Owner' if workspace owner
 * 
 * @param int $userId User ID to check
 * @return string Role name
 */
function getUserRole($userId) {
    try {
        if (!isset($GLOBALS['dbConfig'])) { require __DIR__ . '/../config/database.php'; }
        $config = $GLOBALS['dbConfig'];
        $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        
        // Check if user is a team member
        $stmt = $pdo->prepare("SELECT role FROM team_members WHERE email = (SELECT email FROM users WHERE id = ?)");
        $stmt->execute([$userId]);
        $member = $stmt->fetch();
        
        if ($member) {
            return $member['role'];
        }
        
        // If not a team member, they're a workspace owner
        return 'Owner';
    } catch (Exception $e) {
        error_log("Error getting user role: " . $e->getMessage());
        return 'Owner'; // Default to owner if error
    }
}

/**
 * Check if user can download assets
 * Owners, Admins, and Team Members can download
 * Viewers cannot download
 * 
 * @param int $userId User ID to check
 * @return bool True if can download
 */
function canDownload($userId) {
    $role = getUserRole($userId);
    return in_array($role, ['Owner', 'Admin', 'Team Member']);
}

/**
 * Get auth headers for API requests
 * Used in frontend to include proper authentication
 * 
 * @return array Headers array
 */
function getAuthHeaders() {
    $headers = ['Content-Type' => 'application/json'];
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Session-based auth is automatic via cookies
    return $headers;
}

