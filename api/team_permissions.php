<?php
/**
 * Team Permissions Helper
 * Manages team member permissions for various actions
 */

require_once __DIR__ . '/auth_helper.php';

/**
 * Check if a team member has permission for a specific action
 * 
 * @param PDO $pdo Database connection
 * @param int $userId User ID to check
 * @param string $permissionType Type of permission (upload_assets, create_kits, download_assets, manage_shares)
 * @return bool True if user has permission
 */
function hasTeamPermission($pdo, $userId, $permissionType) {
    try {
        // Get user info to check if they're a team member
        $stmt = $pdo->prepare("
            SELECT u.id, u.workspace_owner_id, u.role,
                   tm.role as team_role, tm.status as team_status
            FROM users u
            LEFT JOIN team_members tm ON u.workspace_owner_id = tm.workspace_owner_id 
                AND u.email = tm.member_email
            WHERE u.id = ?
        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return false;
        }
        
        // If user is a workspace owner, they have all permissions
        if (!$user['workspace_owner_id']) {
            return true;
        }
        
        // If team member is not active, deny permission
        if ($user['team_status'] !== 'active') {
            return false;
        }
        
        // Get workspace owner's permission settings
        $stmt = $pdo->prepare("
            SELECT allowed_roles 
            FROM team_permissions 
            WHERE workspace_owner_id = ? AND permission_type = ?
        ");
        $stmt->execute([$user['workspace_owner_id'], $permissionType]);
        $permission = $stmt->fetch();
        
        // If no specific permission is set, use default permissions
        if (!$permission) {
            return getDefaultPermission($user['team_role'], $permissionType);
        }
        
        $allowedRoles = json_decode($permission['allowed_roles'], true);
        return in_array($user['team_role'], $allowedRoles);
        
    } catch (Exception $e) {
        error_log("Team permission check failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Get default permission for a role and action
 * 
 * @param string $role Team member role
 * @param string $permissionType Type of permission
 * @return bool Default permission
 */
function getDefaultPermission($role, $permissionType) {
    $defaultPermissions = [
        'Admin' => [
            'upload_assets' => true,
            'create_kits' => true,
            'download_assets' => true,
            'manage_shares' => true
        ],
        'Team Member' => [
            'upload_assets' => true,
            'create_kits' => true,
            'download_assets' => false, // Requires approval
            'manage_shares' => false
        ],
        'Viewer' => [
            'upload_assets' => false,
            'create_kits' => false,
            'download_assets' => false,
            'manage_shares' => false
        ]
    ];
    
    return $defaultPermissions[$role][$permissionType] ?? false;
}

/**
 * Set team permissions for a workspace
 * 
 * @param PDO $pdo Database connection
 * @param int $workspaceOwnerId Workspace owner ID
 * @param string $permissionType Type of permission
 * @param array $allowedRoles Array of allowed roles
 * @return bool Success status
 */
function setTeamPermission($pdo, $workspaceOwnerId, $permissionType, $allowedRoles) {
    try {
        // Check if permission already exists
        $stmt = $pdo->prepare("
            SELECT id FROM team_permissions 
            WHERE workspace_owner_id = ? AND permission_type = ?
        ");
        $stmt->execute([$workspaceOwnerId, $permissionType]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Update existing permission
            $stmt = $pdo->prepare("
                UPDATE team_permissions 
                SET allowed_roles = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([json_encode($allowedRoles), $existing['id']]);
        } else {
            // Create new permission
            $stmt = $pdo->prepare("
                INSERT INTO team_permissions (workspace_owner_id, permission_type, allowed_roles)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$workspaceOwnerId, $permissionType, json_encode($allowedRoles)]);
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Failed to set team permission: " . $e->getMessage());
        return false;
    }
}

/**
 * Get all team permissions for a workspace
 * 
 * @param PDO $pdo Database connection
 * @param int $workspaceOwnerId Workspace owner ID
 * @return array Array of permissions
 */
function getTeamPermissions($pdo, $workspaceOwnerId) {
    try {
        $stmt = $pdo->prepare("
            SELECT permission_type, allowed_roles 
            FROM team_permissions 
            WHERE workspace_owner_id = ?
        ");
        $stmt->execute([$workspaceOwnerId]);
        $permissions = $stmt->fetchAll();
        
        $result = [];
        foreach ($permissions as $permission) {
            $result[$permission['permission_type']] = json_decode($permission['allowed_roles'], true);
        }
        
        return $result;
    } catch (Exception $e) {
        error_log("Failed to get team permissions: " . $e->getMessage());
        return [];
    }
}

/**
 * Check if user can download an asset (considering approval requirements)
 * 
 * @param PDO $pdo Database connection
 * @param int $userId User ID
 * @param int $assetId Asset ID
 * @return array Result with 'can_download' and 'requires_approval' flags
 */
function checkDownloadPermission($pdo, $userId, $assetId) {
    try {
        // Get user info
        $stmt = $pdo->prepare("
            SELECT u.id, u.workspace_owner_id, u.role,
                   tm.role as team_role, tm.status as team_status
            FROM users u
            LEFT JOIN team_members tm ON u.workspace_owner_id = tm.workspace_owner_id 
                AND u.email = tm.member_email
            WHERE u.id = ?
        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return ['can_download' => false, 'requires_approval' => false];
        }
        
        // If user is workspace owner, they can always download
        if (!$user['workspace_owner_id']) {
            return ['can_download' => true, 'requires_approval' => false];
        }
        
        // If team member is not active, deny
        if ($user['team_status'] !== 'active') {
            return ['can_download' => false, 'requires_approval' => false];
        }
        
        // Check if user has direct download permission
        if (hasTeamPermission($pdo, $userId, 'download_assets')) {
            return ['can_download' => true, 'requires_approval' => false];
        }
        
        // Check if there's a pending or approved download request
        $stmt = $pdo->prepare("
            SELECT status FROM download_requests 
            WHERE asset_id = ? AND requester_id = ?
            ORDER BY requested_at DESC LIMIT 1
        ");
        $stmt->execute([$assetId, $userId]);
        $request = $stmt->fetch();
        
        if ($request) {
            if ($request['status'] === 'approved') {
                return ['can_download' => true, 'requires_approval' => false];
            } elseif ($request['status'] === 'pending') {
                return ['can_download' => false, 'requires_approval' => true, 'request_status' => 'pending'];
            } else {
                return ['can_download' => false, 'requires_approval' => true, 'request_status' => 'denied'];
            }
        }
        
        // No request exists, user needs to request approval
        return ['can_download' => false, 'requires_approval' => true, 'request_status' => 'none'];
        
    } catch (Exception $e) {
        error_log("Download permission check failed: " . $e->getMessage());
        return ['can_download' => false, 'requires_approval' => false];
    }
}

/**
 * Create a download request
 * 
 * @param PDO $pdo Database connection
 * @param int $userId User ID
 * @param int $assetId Asset ID
 * @return array Result with success status and request ID
 */
function createDownloadRequest($pdo, $userId, $assetId) {
    try {
        // Check if request already exists
        $stmt = $pdo->prepare("
            SELECT id, status FROM download_requests 
            WHERE asset_id = ? AND requester_id = ?
            ORDER BY requested_at DESC LIMIT 1
        ");
        $stmt->execute([$assetId, $userId]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            if ($existing['status'] === 'pending') {
                return ['success' => false, 'message' => 'Download request already pending'];
            } elseif ($existing['status'] === 'approved') {
                return ['success' => false, 'message' => 'Download already approved'];
            }
        }
        
        // Create new request
        $stmt = $pdo->prepare("
            INSERT INTO download_requests (asset_id, requester_id, status)
            VALUES (?, ?, 'pending')
        ");
        $stmt->execute([$assetId, $userId]);
        $requestId = $pdo->lastInsertId();
        
        return ['success' => true, 'request_id' => $requestId];
        
    } catch (Exception $e) {
        error_log("Failed to create download request: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to create download request'];
    }
}
?>

