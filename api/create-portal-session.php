<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/env.php';
require_once '../config/database.php';
require_once '../app/Services/StripeService.php';

try {
    // Get current user (you'll need to implement proper authentication)
    $userId = getCurrentUserId();
    $customerId = getStripeCustomerId($userId);
    
    if (!$customerId) {
        throw new Exception('No Stripe customer found');
    }
    
    // Initialize Stripe
    $stripe = new StripeService();
    
    // Create portal session
    $session = $stripe->createPortalSession([
        'customer' => $customerId,
        'return_url' => 'https://yourdomain.com/dashboard/billing'
    ]);
    
    // Redirect to portal
    header('Location: ' . $session->url);
    exit;
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}

function getCurrentUserId() {
    // Implement proper authentication here
    return 1;
}

function getStripeCustomerId($userId) {
    // Query database for user's Stripe customer ID
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT stripe_customer_id FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    return $user ? $user['stripe_customer_id'] : null;
}
?>
