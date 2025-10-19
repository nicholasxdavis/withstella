<?php
require_once '../config/env.php';
require_once '../config/database.php';
require_once '../app/Services/StripeService.php';

// Set your webhook endpoint secret from environment variable
// Try both webhook secrets (full and thin payload)
$endpoint_secret = $_ENV['STRIPE_WEBHOOK_SECRET'] ?? $_ENV['STRIPE_WB'] ?? $_ENV['STRIPE_WB_THIN'] ?? '';

$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
$event = null;

try {
    $event = \Stripe\Webhook::constructEvent(
        $payload, $sig_header, $endpoint_secret
    );
    
    // Log successful webhook verification
    error_log("Stripe webhook verified successfully: " . $event->type);
    
} catch(\UnexpectedValueException $e) {
    // Invalid payload
    error_log("Stripe webhook error - Invalid payload: " . $e->getMessage());
    http_response_code(400);
    exit();
} catch(\Stripe\Exception\SignatureVerificationException $e) {
    // Invalid signature
    error_log("Stripe webhook error - Invalid signature: " . $e->getMessage());
    http_response_code(400);
    exit();
}

// Handle the event
error_log("Processing Stripe webhook event: " . $event->type);

switch ($event->type) {
    case 'checkout.session.completed':
        $session = $event->data->object;
        error_log("Handling checkout.session.completed for session: " . $session->id);
        handleCheckoutCompleted($session);
        break;
        
    case 'customer.subscription.created':
        $subscription = $event->data->object;
        error_log("Handling customer.subscription.created for subscription: " . $subscription->id);
        handleSubscriptionCreated($subscription);
        break;
        
    case 'customer.subscription.updated':
        $subscription = $event->data->object;
        error_log("Handling customer.subscription.updated for subscription: " . $subscription->id);
        handleSubscriptionUpdated($subscription);
        break;
        
    case 'customer.subscription.deleted':
        $subscription = $event->data->object;
        error_log("Handling customer.subscription.deleted for subscription: " . $subscription->id);
        handleSubscriptionDeleted($subscription);
        break;
        
    case 'invoice.payment_succeeded':
        $invoice = $event->data->object;
        error_log("Handling invoice.payment_succeeded for invoice: " . $invoice->id);
        handlePaymentSucceeded($invoice);
        break;
        
    case 'invoice.payment_failed':
        $invoice = $event->data->object;
        error_log("Handling invoice.payment_failed for invoice: " . $invoice->id);
        handlePaymentFailed($invoice);
        break;
        
    default:
        // Unexpected event type
        http_response_code(400);
        exit();
}

http_response_code(200);

function handleCheckoutCompleted($session) {
    global $pdo;
    
    $userId = $session->metadata->user_id ?? null;
    $customerId = $session->customer;
    
    if ($userId) {
        // Update user with Stripe customer ID
        $stmt = $pdo->prepare("UPDATE users SET stripe_customer_id = ? WHERE id = ?");
        $stmt->execute([$customerId, $userId]);
    }
}

function handleSubscriptionCreated($subscription) {
    global $pdo;
    
    $customerId = $subscription->customer;
    
    // Find user by customer ID
    $stmt = $pdo->prepare("SELECT id FROM users WHERE stripe_customer_id = ?");
    $stmt->execute([$customerId]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Update user plan to pro
        $stmt = $pdo->prepare("UPDATE users SET plan_type = 'pro', stripe_subscription_id = ? WHERE id = ?");
        $stmt->execute([$subscription->id, $user['id']]);
    }
}

function handleSubscriptionUpdated($subscription) {
    global $pdo;
    
    $customerId = $subscription->customer;
    $status = $subscription->status;
    
    // Find user by customer ID
    $stmt = $pdo->prepare("SELECT id FROM users WHERE stripe_customer_id = ?");
    $stmt->execute([$customerId]);
    $user = $stmt->fetch();
    
    if ($user) {
        if ($status === 'active') {
            $stmt = $pdo->prepare("UPDATE users SET plan_type = 'pro' WHERE id = ?");
            $stmt->execute([$user['id']]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET plan_type = 'free' WHERE id = ?");
            $stmt->execute([$user['id']]);
        }
    }
}

function handleSubscriptionDeleted($subscription) {
    global $pdo;
    
    $customerId = $subscription->customer;
    
    // Find user by customer ID
    $stmt = $pdo->prepare("SELECT id FROM users WHERE stripe_customer_id = ?");
    $stmt->execute([$customerId]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Downgrade user to free plan
        $stmt = $pdo->prepare("UPDATE users SET plan_type = 'free', stripe_subscription_id = NULL WHERE id = ?");
        $stmt->execute([$user['id']]);
    }
}

function handlePaymentSucceeded($invoice) {
    // Log successful payment
    error_log("Payment succeeded for invoice: " . $invoice->id);
}

function handlePaymentFailed($invoice) {
    // Log failed payment
    error_log("Payment failed for invoice: " . $invoice->id);
    
    // You might want to send an email notification here
}
?>
