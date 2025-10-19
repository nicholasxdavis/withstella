<?php
error_reporting(0);
ini_set('display_errors', 0);

// Configure session before any output
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Lax');
session_name('STELLA_SESSION');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Helper function to send JSON response
function sendJson($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit();
}

// Database credentials - Coolify/MariaDB
$host = getenv('DB_HOST') ?: 'mariadb-database-rgcs4ksokcww0g04wkwg4g4k';
$dbname = getenv('DB_DATABASE') ?: 'default';
$user = getenv('DB_USERNAME') ?: 'mariadb';
$pass = getenv('DB_PASSWORD') ?: 'ba55Ko1lA8FataxMYnpl9qVploHFJXZKqCvfnwrlcxvISIqbQusX4qFeELhdYPdO';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    sendJson(['success' => false, 'message' => 'Database connection failed'], 500);
}

// Get request data
$request_body = file_get_contents('php://input');
$data = json_decode($request_body, true);
$action = $_GET['action'] ?? null;

// Handle different actions
switch ($action) {
    case 'register':
        handleRegister($pdo, $data);
        break;
    
    case 'login':
        handleLogin($pdo, $data);
        break;
    
    case 'forgot-password':
        handleForgotPassword($pdo, $data);
        break;
    
    case 'reset-password':
        handleResetPassword($pdo, $data);
        break;
    
    case 'verify-invite':
        handleVerifyInvite($pdo, $data);
        break;
    
    case 'accept-invite':
        handleAcceptInvite($pdo, $data);
        break;
    
    default:
        sendJson(['success' => false, 'message' => 'Invalid action'], 400);
}

/**
 * Handle user registration
 */
function handleRegister($pdo, $data) {
    try {
        $full_name = trim($data['full_name'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        
        if (empty($full_name) || empty($email) || empty($password)) {
            sendJson(['success' => false, 'message' => 'All fields are required'], 422);
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            sendJson(['success' => false, 'message' => 'Invalid email address'], 422);
        }
        
        if (strlen($password) < 8) {
            sendJson(['success' => false, 'message' => 'Password must be at least 8 characters'], 422);
        }
        
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            sendJson(['success' => false, 'message' => 'Email already registered'], 422);
        }
        
        // Create Nextcloud account for this user
        $nextcloudUsername = null;
        $nextcloudPassword = null;
        $nextcloudCreated = false;
        
        try {
            require_once dirname(__DIR__) . '/app/Services/NextcloudUserManager.php';
            
            $nextcloudManager = new \App\Services\NextcloudUserManager();
            $tempPassword = \App\Services\NextcloudUserManager::generatePassword(16);
            
            $nextcloudResult = $nextcloudManager->createUser($email, $tempPassword, $full_name);
            
            if ($nextcloudResult['success']) {
                $nextcloudUsername = $nextcloudResult['username'];
                $nextcloudPassword = $tempPassword; // Store the password
                $nextcloudCreated = true;
            } else {
                error_log('Nextcloud account creation failed for ' . $email . ': ' . $nextcloudResult['message']);
            }
        } catch (Exception $ncException) {
            error_log('Nextcloud exception for ' . $email . ': ' . $ncException->getMessage());
        }
        
        // Create user in database
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, nextcloud_username, nextcloud_password, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
        $stmt->execute([$full_name, $email, $hashed_password, $nextcloudUsername, $nextcloudPassword]);
        $user_id = $pdo->lastInsertId();
        $token = bin2hex(random_bytes(32));
        
        // Start PHP session and store user data
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $_SESSION['user_id'] = (int)$user_id;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_name'] = $full_name;
        $_SESSION['user_full_name'] = $full_name;
        
        sendJson([
            'success' => true,
            'user' => [
                'id' => (int)$user_id,
                'full_name' => $full_name,
                'email' => $email
            ],
            'token' => $token,
            'nextcloud_created' => $nextcloudCreated
        ]);
    } catch (PDOException $e) {
        sendJson(['success' => false, 'message' => 'Database error. Please initialize database first.'], 500);
    } catch (Exception $e) {
        sendJson(['success' => false, 'message' => 'An error occurred'], 500);
    }
}

/**
 * Handle user login
 */
function handleLogin($pdo, $data) {
    try {
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $remember_me = $data['remember_me'] ?? false;
        
        if (empty($email) || empty($password)) {
            sendJson(['success' => false, 'message' => 'Email and password are required'], 422);
        }
        
        // Find user with plan information
        $stmt = $pdo->prepare("
            SELECT id, full_name, email, password, 
                   plan_type, stripe_customer_id, stripe_subscription_id, subscription_status
            FROM users 
            WHERE email = ?
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($password, $user['password'])) {
            sendJson(['success' => false, 'message' => 'Invalid credentials'], 401);
        }
        
        // Start PHP session and store user data
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_full_name'] = $user['full_name'];
        $_SESSION['user_plan'] = $user['plan_type'] ?? 'free';
        
        // Generate session token
        $token = bin2hex(random_bytes(32));
        
        sendJson([
            'success' => true,
            'user' => [
                'id' => (int)$user['id'],
                'full_name' => $user['full_name'],
                'email' => $user['email'],
                'plan_type' => $user['plan_type'] ?? 'free',
                'subscription_status' => $user['subscription_status'] ?? 'inactive'
            ],
            'token' => $token,
            'remember_me' => $remember_me
        ]);
    } catch (PDOException $e) {
        sendJson(['success' => false, 'message' => 'Database error. Please initialize database first.'], 500);
    } catch (Exception $e) {
        sendJson(['success' => false, 'message' => 'An error occurred'], 500);
    }
}

/**
 * Handle forgot password request
 */
function handleForgotPassword($pdo, $data) {
    try {
        $email = trim($data['email'] ?? '');
        
        if (empty($email)) {
            sendJson(['success' => false, 'message' => 'Email is required'], 422);
        }
        
        // Check if user exists
        $stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            // Don't reveal if email exists for security
            sendJson([
                'success' => true,
                'message' => 'If this email is registered, you will receive a password reset link',
                'send_email' => false
            ]);
        }
        
        // Generate reset token
        $token = bin2hex(random_bytes(32));
        
        // Store reset token
        $stmt = $pdo->prepare("REPLACE INTO password_reset_tokens (email, token, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$email, $token]);
        
        // Get base URL
        $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $baseUrl = $scheme . '://' . $host;
        $resetUrl = $baseUrl . '/public/reset-password.php?token=' . $token . '&email=' . urlencode($email);
        
        sendJson([
            'success' => true,
            'message' => 'Password reset link sent to your email',
            'send_email' => true,
            'email_data' => [
                'to_email' => $email,
                'to_name' => $user['full_name'],
                'reset_url' => $resetUrl
            ]
        ]);
    } catch (PDOException $e) {
        sendJson(['success' => false, 'message' => 'Database error. Please initialize database first.'], 500);
    } catch (Exception $e) {
        sendJson(['success' => false, 'message' => 'An error occurred'], 500);
    }
}

/**
 * Handle password reset
 */
function handleResetPassword($pdo, $data) {
    try {
        $email = trim($data['email'] ?? '');
        $token = $data['token'] ?? '';
        $newPassword = $data['password'] ?? '';
        
        if (empty($email) || empty($token) || empty($newPassword)) {
            sendJson(['success' => false, 'message' => 'All fields are required'], 422);
        }
        
        if (strlen($newPassword) < 8) {
            sendJson(['success' => false, 'message' => 'Password must be at least 8 characters'], 422);
        }
        
        // Verify token exists and is not expired (30 minutes)
        $stmt = $pdo->prepare("SELECT * FROM password_reset_tokens WHERE email = ? AND token = ? AND created_at > DATE_SUB(NOW(), INTERVAL 30 MINUTE)");
        $stmt->execute([$email, $token]);
        $resetToken = $stmt->fetch();
        
        if (!$resetToken) {
            sendJson(['success' => false, 'message' => 'Invalid or expired reset token'], 400);
        }
        
        // Update user password
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE email = ?");
        $stmt->execute([$hashedPassword, $email]);
        
        // Delete used token
        $stmt = $pdo->prepare("DELETE FROM password_reset_tokens WHERE email = ?");
        $stmt->execute([$email]);
        
        sendJson([
            'success' => true,
            'message' => 'Password reset successfully'
        ]);
    } catch (PDOException $e) {
        sendJson(['success' => false, 'message' => 'Database error'], 500);
    } catch (Exception $e) {
        sendJson(['success' => false, 'message' => 'An error occurred'], 500);
    }
}

/**
 * Handle invite verification
 */
function handleVerifyInvite($pdo, $data) {
    try {
        $token = trim($data['token'] ?? '');
        
        if (empty($token)) {
            sendJson(['success' => false, 'message' => 'Invalid invite token'], 422);
        }
        
        // Find pending invite
        $stmt = $pdo->prepare("
            SELECT pi.*, u.full_name as inviter_name, u.email as inviter_email 
            FROM pending_invites pi
            JOIN users u ON pi.inviter_id = u.id
            WHERE pi.token = ? AND pi.expires_at > NOW()
        ");
        $stmt->execute([$token]);
        $invite = $stmt->fetch();
        
        if (!$invite) {
            sendJson(['success' => false, 'message' => 'Invalid or expired invite link'], 404);
        }
        
        // Check if email is already registered
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$invite['email']]);
        if ($stmt->fetch()) {
            sendJson(['success' => false, 'message' => 'This email is already registered. Please login instead.'], 422);
        }
        
        sendJson([
            'success' => true,
            'invite' => [
                'email' => $invite['email'],
                'role' => $invite['role'],
                'inviter_name' => $invite['inviter_name'],
                'inviter_email' => $invite['inviter_email'],
                'inviter_id' => $invite['inviter_id']
            ]
        ]);
    } catch (PDOException $e) {
        sendJson(['success' => false, 'message' => 'Database error'], 500);
    }
}

/**
 * Handle invite acceptance (create team member account)
 */
function handleAcceptInvite($pdo, $data) {
    try {
        $token = trim($data['token'] ?? '');
        $full_name = trim($data['full_name'] ?? '');
        $password = $data['password'] ?? '';
        
        if (empty($token) || empty($full_name) || empty($password)) {
            sendJson(['success' => false, 'message' => 'All fields are required'], 422);
        }
        
        if (strlen($password) < 8) {
            sendJson(['success' => false, 'message' => 'Password must be at least 8 characters'], 422);
        }
        
        // Find and verify invite
        $stmt = $pdo->prepare("
            SELECT * FROM pending_invites 
            WHERE token = ? AND expires_at > NOW()
        ");
        $stmt->execute([$token]);
        $invite = $stmt->fetch();
        
        if (!$invite) {
            sendJson(['success' => false, 'message' => 'Invalid or expired invite link'], 404);
        }
        
        // Check if email is already registered
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$invite['email']]);
        if ($stmt->fetch()) {
            sendJson(['success' => false, 'message' => 'This email is already registered'], 422);
        }
        
        // Create user account as team member
        // They will share the workspace owner's Nextcloud account
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        
        $stmt = $pdo->prepare("
            INSERT INTO users (
                full_name, 
                email, 
                password, 
                workspace_owner_id, 
                role,
                created_at,
                updated_at
            ) VALUES (?, ?, ?, ?, 'team_member', NOW(), NOW())
        ");
        $stmt->execute([
            $full_name,
            $invite['email'],
            $hashedPassword,
            $invite['inviter_id']
        ]);
        
        $userId = $pdo->lastInsertId();
        
        // Add to team_members table for the workspace owner
        $stmt = $pdo->prepare("
            INSERT INTO team_members (
                workspace_owner_id,
                member_email,
                role,
                status,
                invited_at,
                updated_at
            ) VALUES (?, ?, ?, 'active', NOW(), NOW())
        ");
        $stmt->execute([
            $invite['inviter_id'],
            $invite['email'],
            $invite['role']
        ]);
        
        // Delete the used invite
        $stmt = $pdo->prepare("DELETE FROM pending_invites WHERE token = ?");
        $stmt->execute([$token]);
        
        // Start PHP session and store user data for auto-login
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $_SESSION['user_id'] = (int)$userId;
        $_SESSION['user_email'] = $invite['email'];
        $_SESSION['user_name'] = $full_name;
        $_SESSION['user_full_name'] = $full_name;
        
        // Return user data for auto-login
        $stmt = $pdo->prepare("SELECT id, full_name, email, workspace_owner_id, role FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        sendJson([
            'success' => true,
            'message' => 'Account created successfully! Welcome to the team.',
            'user' => $user
        ]);
        
    } catch (PDOException $e) {
        sendJson(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
    }
}
