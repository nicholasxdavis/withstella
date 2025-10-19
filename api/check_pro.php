<?php
/**
 * Pro Plan Check Helper
 * Verifies if a user has an active Pro subscription
 */

function requireProPlan($pdo, $userId) {
    try {
        $stmt = $pdo->prepare("
            SELECT plan_type, subscription_status 
            FROM users 
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'User not found',
                'requires_pro' => true
            ]);
            exit;
        }
        
        // Check if user has pro plan
        if ($user['plan_type'] !== 'pro') {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'This feature requires a Pro subscription',
                'requires_pro' => true,
                'current_plan' => $user['plan_type'],
                'upgrade_url' => '/dashboard/?page=billing'
            ]);
            exit;
        }
        
        return true;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to verify subscription',
            'requires_pro' => true
        ]);
        exit;
    }
}
?>

