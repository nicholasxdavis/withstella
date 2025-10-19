<?php
/**
 * Upgrade Plan API
 * Called after successful Stripe checkout to update user's plan in database
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-User-ID');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Load dependencies
require_once __DIR__ . '/../config/env.php';

// Load Stripe
$autoloadPaths = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../vendor/autoload.php',
];

$autoloadLoaded = false;
foreach ($autoloadPaths as $autoloadPath) {
    if (file_exists($autoloadPath)) {
        require_once $autoloadPath;
        $autoloadLoaded = true;
        break;
    }
}

// Database credentials
$host = getenv('DB_HOST') ?: 'mariadb-database-rgcs4ksokcww0g04wkwg4g4k';
$dbname = getenv('DB_DATABASE') ?: 'default';
$user = getenv('DB_USERNAME') ?: 'mariadb';
$pass = getenv('DB_PASSWORD') ?: 'ba55Ko1lA8FataxMYnpl9qVploHFJXZKqCvfnwrlcxvISIqbQusX4qFeELhdYPdO';

try {
    // Get request data
    $input = json_decode(file_get_contents('php://input'), true);
    $sessionId = $input['session_id'] ?? null;
    
    if (!$sessionId) {
        throw new Exception('Session ID is required');
    }
    
    // Get user info
    session_start();
    $userId = $_SESSION['user_id'] ?? $_SERVER['HTTP_X_USER_ID'] ?? $input['user_id'] ?? null;
    
    if (!$userId) {
        throw new Exception('User authentication required');
    }
    
    // Initialize Stripe
    if (!class_exists('Stripe\Stripe')) {
        throw new Exception('Stripe library not loaded');
    }
    
    \Stripe\Stripe::setApiKey($_ENV['STRIPE_SK'] ?? '');
    
    // Retrieve the session from Stripe to verify it's valid
    $session = \Stripe\Checkout\Session::retrieve($sessionId);
    
    if (!$session || $session->payment_status !== 'paid') {
        throw new Exception('Payment not confirmed');
    }
    
    // Get database connection
    $pdo = new PDO(
        "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    // Get subscription ID if available
    $subscriptionId = $session->subscription ?? null;
    $customerId = $session->customer;
    
    // Update user plan in database
    $stmt = $pdo->prepare("
        UPDATE users 
        SET plan_type = 'pro',
            stripe_customer_id = ?,
            stripe_subscription_id = ?,
            subscription_status = 'active',
            updated_at = NOW()
        WHERE id = ?
    ");
    
    $stmt->execute([$customerId, $subscriptionId, $userId]);
    
    // Log the upgrade
    $logStmt = $pdo->prepare("
        INSERT INTO activities (user_id, type, description, created_at)
        VALUES (?, 'upgrade', 'Upgraded to Pro plan', NOW())
    ");
    $logStmt->execute([$userId]);
    
    // Update session
    $_SESSION['user_plan'] = 'pro';
    
    echo json_encode([
        'success' => true,
        'message' => 'Plan upgraded successfully',
        'plan' => 'pro',
        'subscription_id' => $subscriptionId
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>

