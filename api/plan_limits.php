<?php
/**
 * Plan Limits Helper
 * Checks if user has reached their plan limits
 */

class PlanLimits {
    private $pdo;
    
    // Free plan limits
    const FREE_BRAND_KITS = 1;
    const FREE_TEAM_MEMBERS = 2;
    const FREE_STORAGE_GB = 1;
    
    // Pro plan limits (unlimited)
    const PRO_BRAND_KITS = PHP_INT_MAX;
    const PRO_TEAM_MEMBERS = PHP_INT_MAX;
    const PRO_STORAGE_GB = PHP_INT_MAX;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Get user's effective plan (considers if they're a team member of a Pro owner)
     */
    public function getEffectivePlan($userId) {
        // Get user's own plan
        $stmt = $this->pdo->prepare("SELECT plan_type FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return 'free';
        }
        
        // If user is already Pro, return Pro
        if ($user['plan_type'] === 'pro') {
            return 'pro';
        }
        
        // Check if user is a team member of a Pro owner
        // Look in team_members table to see if this user is invited by someone
        $stmt = $this->pdo->prepare("
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
            return 'pro';
        }
        
        return $user['plan_type'] ?? 'free';
    }
    
    /**
     * Check if user can create another brand kit
     */
    public function canCreateBrandKit($userId) {
        $plan = $this->getEffectivePlan($userId);
        
        if ($plan === 'pro') {
            return ['allowed' => true];
        }
        
        // Count current brand kits
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM brand_kits WHERE user_id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $currentCount = $result['count'] ?? 0;
        
        $allowed = $currentCount < self::FREE_BRAND_KITS;
        
        return [
            'allowed' => $allowed,
            'current' => $currentCount,
            'limit' => self::FREE_BRAND_KITS,
            'message' => $allowed ? null : "You've reached your free plan limit of " . self::FREE_BRAND_KITS . " brand kit. Upgrade to Pro for unlimited brand kits."
        ];
    }
    
    /**
     * Check if user can invite another team member
     */
    public function canInviteTeamMember($userId) {
        $plan = $this->getEffectivePlan($userId);
        
        if ($plan === 'pro') {
            return ['allowed' => true];
        }
        
        // Count current team members (active + pending)
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as count 
            FROM team_members 
            WHERE user_id = ? 
            AND status IN ('active', 'pending')
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $currentCount = $result['count'] ?? 0;
        
        $allowed = $currentCount < self::FREE_TEAM_MEMBERS;
        
        return [
            'allowed' => $allowed,
            'current' => $currentCount,
            'limit' => self::FREE_TEAM_MEMBERS,
            'message' => $allowed ? null : "You've reached your free plan limit of " . self::FREE_TEAM_MEMBERS . " team members. Upgrade to Pro for unlimited team members."
        ];
    }
    
    /**
     * Check if user can upload file (storage limit)
     */
    public function canUploadFile($userId, $fileSizeBytes) {
        $plan = $this->getEffectivePlan($userId);
        
        if ($plan === 'pro') {
            return ['allowed' => true];
        }
        
        // Calculate current storage usage
        $stmt = $this->pdo->prepare("
            SELECT COALESCE(SUM(file_size), 0) as total_bytes 
            FROM assets 
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $currentBytes = $result['total_bytes'] ?? 0;
        
        $limitBytes = self::FREE_STORAGE_GB * 1024 * 1024 * 1024; // Convert GB to bytes
        $afterUploadBytes = $currentBytes + $fileSizeBytes;
        $allowed = $afterUploadBytes <= $limitBytes;
        
        $currentGB = round($currentBytes / (1024 * 1024 * 1024), 2);
        $afterUploadGB = round($afterUploadBytes / (1024 * 1024 * 1024), 2);
        
        return [
            'allowed' => $allowed,
            'current_bytes' => $currentBytes,
            'current_gb' => $currentGB,
            'after_upload_gb' => $afterUploadGB,
            'limit_gb' => self::FREE_STORAGE_GB,
            'message' => $allowed ? null : "This upload would exceed your " . self::FREE_STORAGE_GB . "GB storage limit (you'd use {$afterUploadGB}GB). Upgrade to Pro for unlimited storage."
        ];
    }
    
    /**
     * Get storage usage statistics
     */
    public function getStorageStats($userId) {
        $plan = $this->getEffectivePlan($userId);
        
        $stmt = $this->pdo->prepare("
            SELECT COALESCE(SUM(file_size), 0) as total_bytes,
                   COUNT(*) as file_count
            FROM assets 
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $usedBytes = $result['total_bytes'] ?? 0;
        $usedGB = round($usedBytes / (1024 * 1024 * 1024), 2);
        $limitGB = $plan === 'pro' ? 'Unlimited' : self::FREE_STORAGE_GB;
        $percentUsed = $plan === 'pro' ? 0 : round(($usedBytes / (self::FREE_STORAGE_GB * 1024 * 1024 * 1024)) * 100, 1);
        
        return [
            'plan' => $plan,
            'used_bytes' => $usedBytes,
            'used_gb' => $usedGB,
            'limit_gb' => $limitGB,
            'percent_used' => $percentUsed,
            'file_count' => $result['file_count'] ?? 0
        ];
    }
}
?>

