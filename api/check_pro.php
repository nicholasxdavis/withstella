<?php
/**
 * Pro Plan Check Helper
 * Verifies if a user has an active Pro subscription
 */

function requireProPlan($pdo, $userId) {
    try {
        // Get user's own plan
        $stmt = $pdo->prepare("SELECT plan_type FROM users WHERE id = ?");
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
        
        // If user is already Pro, grant access
        if ($user['plan_type'] === 'pro') {
            return true;
        }
        
        // Check if user is a team member of a Pro owner (inherit Pro benefits)
        $stmt = $pdo->prepare("
            SELECT u.plan_type as owner_plan
            FROM team_members tm
            JOIN users u ON tm.user_id = u.id
            WHERE tm.member_user_id = ?
            AND tm.status = 'active'
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        $ownerData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // If they're on a Pro owner's team, they inherit Pro benefits
        if ($ownerData && $ownerData['owner_plan'] === 'pro') {
            return true;
        }
        
        // Not Pro and not on a Pro team - deny access
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'This feature requires a Pro subscription',
            'requires_pro' => true,
            'current_plan' => $user['plan_type'],
            'upgrade_url' => '/dashboard/?page=billing'
        ]);
        exit;
        
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

