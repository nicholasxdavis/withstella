<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Suppress errors for cleaner JSON output
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../config/env.php';

// Load composer autoload - try multiple paths
$autoloadPaths = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../vendor/autoload.php',
    __DIR__ . '/../../../vendor/autoload.php'
];

$autoloadLoaded = false;
foreach ($autoloadPaths as $autoloadPath) {
    if (file_exists($autoloadPath)) {
        require_once $autoloadPath;
        $autoloadLoaded = true;
        break;
    }
}

try {
    // Get request data
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Use environment variable for price ID if not provided
    $priceId = $input['priceId'] ?? $_ENV['STRIPE_PRICE_ID'] ?? '';
    
    if (empty($priceId)) {
        throw new Exception('Price ID is required. Please set STRIPE_PRICE_ID in environment variables.');
    }
    
    // Check if Stripe is available after autoload
    if (!$autoloadLoaded || !class_exists('Stripe\Stripe')) {
        // Stripe library not installed, return error
        throw new Exception('Stripe library not installed. Please run: composer install');
    }
    
    // Initialize Stripe
    require_once __DIR__ . '/../app/Services/StripeService.php';
    $stripe = new App\Services\StripeService();
    
    // Try to get user info from session or headers
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $userId = $_SESSION['user_id'] ?? $_SERVER['HTTP_X_USER_ID'] ?? $input['userId'] ?? null;
    $userEmail = $_SESSION['user_email'] ?? $input['customerEmail'] ?? 'guest@example.com';
    
    // If no user ID, try to get from database by email
    if (!$userId && $userEmail !== 'guest@example.com') {
        $dbConfig = require __DIR__ . '/../config/database.php';
        $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['database']};charset=utf8mb4";
        $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password']);
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$userEmail]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $userId = $user['id'];
        }
    }
    
    // Create checkout session with user_id in metadata
    $session = $stripe->createCheckoutSession([
        'price_id' => $priceId,
        'success_url' => $input['successUrl'] ?? 'https://yourdomain.com/dashboard/?success=true&session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => $input['cancelUrl'] ?? 'https://yourdomain.com/dashboard/billing',
        'customer_email' => $userEmail,
        'metadata' => [
            'user_id' => $userId ?? 0
        ]
    ]);
    
    echo json_encode(['sessionId' => $session->id]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}

?>
