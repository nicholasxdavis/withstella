<?php
error_reporting(0);
ini_set('display_errors', 0);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-User-ID, X-Requested-With, X-User-Email');
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

// Get authenticated user ID from header
$userId = $_SERVER['HTTP_X_USER_ID'] ?? null;
if (!$userId) {
    sendJson(['success' => false, 'message' => 'Not authenticated'], 401);
}
$userId = (int)$userId;

// Resolve owner context (workspace owner id if team member)
function getOwnerContext(PDO $pdo, int $userId): array {
    $stmt = $pdo->prepare("SELECT id, email, role, workspace_owner_id FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    if (!$user) return ['owner_id' => $userId, 'user' => null];
    $ownerId = $user['workspace_owner_id'] ? (int)$user['workspace_owner_id'] : (int)$user['id'];
    return ['owner_id' => $ownerId, 'user' => $user];
}

// Get request data
$request_body = file_get_contents('php://input');
$data = json_decode($request_body, true);
$action = $_GET['action'] ?? null;

// Handle different actions
switch ($action) {
    case 'create-invite':
        handleCreateInvite($pdo, $userId, $data);
        break;

    case 'list-members':
        handleListMembers($pdo, $userId);
        break;

    case 'list-pending-invites':
        handleListInvites($pdo, $userId);
        break;

    case 'update-role':
        handleUpdateRole($pdo, $userId, $data);
        break;

    case 'remove-member':
        handleRemoveMember($pdo, $userId, $data);
        break;

    case 'revoke-invite':
        handleRevokeInvite($pdo, $userId, $data);
        break;
    
    default:
        sendJson(['success' => false, 'message' => 'Invalid action'], 400);
}

/**
 * Create pending invite
 */
function handleCreateInvite($pdo, $userId, $data) {
    try {
        $email = trim($data['email'] ?? '');
        $token = trim($data['token'] ?? '');
        $role = trim($data['role'] ?? 'Team Member');
        $ctx = getOwnerContext($pdo, (int)$userId);
        $ownerId = $ctx['owner_id'];
        
        // 🔒 CHECK PLAN LIMIT - Team Members
        require_once __DIR__ . '/plan_limits.php';
        $planLimits = new PlanLimits($pdo);
        $canInvite = $planLimits->canInviteTeamMember($ownerId);
        if (!$canInvite['allowed']) {
            sendJson([
                'success' => false,
                'message' => $canInvite['message'],
                'limit_reached' => true,
                'current' => $canInvite['current'],
                'limit' => $canInvite['limit'],
                'upgrade_required' => true
            ], 403);
        }
        
        // Validate role
        $validRoles = ['Admin', 'Team Member', 'Viewer'];
        if (!in_array($role, $validRoles)) {
            $role = 'Team Member';
        }
        
        if (empty($email) || empty($token)) {
            sendJson(['success' => false, 'message' => 'Email and token are required'], 422);
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            sendJson(['success' => false, 'message' => 'Invalid email address'], 422);
        }
        
        // Check if user is already registered
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            sendJson(['success' => false, 'message' => 'This email is already registered'], 422);
        }
        
        // Check if active invite already exists (not expired)
        $stmt = $pdo->prepare("SELECT id FROM pending_invites WHERE inviter_id = ? AND email = ? AND expires_at > NOW()");
        $stmt->execute([$ownerId, $email]);
        if ($stmt->fetch()) {
            sendJson(['success' => false, 'message' => 'Active invite already sent to this email'], 422);
        }
        
        // Delete any expired invites for this email to allow re-inviting
        $stmt = $pdo->prepare("DELETE FROM pending_invites WHERE inviter_id = ? AND email = ? AND expires_at < NOW()");
        $stmt->execute([$ownerId, $email]);
        
        // Create invite (expires in 7 days)
        $expiresAt = date('Y-m-d H:i:s', strtotime('+7 days'));
        
        $stmt = $pdo->prepare("
            INSERT INTO pending_invites (
                inviter_id,
                email,
                token,
                role,
                created_at,
                expires_at
            ) VALUES (?, ?, ?, ?, NOW(), ?)
        ");
        $stmt->execute([$ownerId, $email, $token, $role, $expiresAt]);
        
        sendJson([
            'success' => true,
            'message' => 'Invite created successfully',
            'invite_id' => $pdo->lastInsertId()
        ]);
        
    } catch (PDOException $e) {
        sendJson(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
    }
}

/**
 * List members for the workspace owner
 */
function handleListMembers(PDO $pdo, int $userId) {
    try {
        $ctx = getOwnerContext($pdo, $userId);
        $ownerId = $ctx['owner_id'];
        // Owner + members
        $stmt = $pdo->prepare("SELECT id, full_name, email, role, workspace_owner_id, created_at FROM users WHERE id = ? OR workspace_owner_id = ? ORDER BY (id = ?) DESC, created_at DESC");
        $stmt->execute([$ownerId, $ownerId, $ownerId]);
        $users = $stmt->fetchAll();
        // Join team_members metadata (role/status)
        $tm = $pdo->prepare("SELECT member_email, role, status, invited_at, updated_at FROM team_members WHERE workspace_owner_id = ?");
        $tm->execute([$ownerId]);
        $teamMeta = $tm->fetchAll();
        $emailToMeta = [];
        foreach ($teamMeta as $m) { $emailToMeta[$m['member_email']] = $m; }
        foreach ($users as &$u) {
            if ((int)$u['id'] === $ownerId) { $u['team_role'] = 'Owner'; $u['team_status'] = 'active'; continue; }
            $meta = $emailToMeta[$u['email']] ?? null;
            $u['team_role'] = $meta['role'] ?? ($u['role'] ?: 'Team Member');
            $u['team_status'] = $meta['status'] ?? 'active';
        }
        sendJson(['success' => true, 'members' => $users]);
    } catch (PDOException $e) {
        sendJson(['success' => false, 'message' => 'Database error'], 500);
    }
}

/**
 * List pending invites
 */
function handleListInvites(PDO $pdo, int $userId) {
    try {
        $ctx = getOwnerContext($pdo, $userId);
        $ownerId = $ctx['owner_id'];
        $stmt = $pdo->prepare("SELECT email, role, token, expires_at, created_at FROM pending_invites WHERE inviter_id = ? AND expires_at > NOW() ORDER BY created_at DESC");
        $stmt->execute([$ownerId]);
        $rows = $stmt->fetchAll();
        sendJson(['success' => true, 'invites' => $rows]);
    } catch (PDOException $e) {
        sendJson(['success' => false, 'message' => 'Database error'], 500);
    }
}

/**
 * Update member role
 */
function handleUpdateRole(PDO $pdo, int $userId, array $data) {
    try {
        $email = trim($data['email'] ?? '');
        $newRole = trim($data['role'] ?? '');
        $validRoles = ['Admin', 'Team Member', 'Viewer'];
        if (!$email || !in_array($newRole, $validRoles)) {
            sendJson(['success' => false, 'message' => 'Email and valid role are required'], 422);
        }
        $ctx = getOwnerContext($pdo, $userId);
        $ownerId = $ctx['owner_id'];
        // Prevent changing owner role
        $chk = $pdo->prepare("SELECT id, email, workspace_owner_id FROM users WHERE email = ?");
        $chk->execute([$email]);
        $usr = $chk->fetch();
        if ($usr && (int)$usr['id'] === $ownerId) {
            sendJson(['success' => false, 'message' => 'Cannot change owner role'], 403);
        }
        // Update team_members view role
        $pdo->prepare("UPDATE team_members SET role = ?, updated_at = NOW() WHERE workspace_owner_id = ? AND member_email = ?")
            ->execute([$newRole, $ownerId, $email]);
        // If user belongs to this workspace, mirror role in users table
        if ($usr && ((int)$usr['workspace_owner_id'] === $ownerId)) {
            $pdo->prepare("UPDATE users SET role = ?, updated_at = NOW() WHERE id = ?")
                ->execute([$newRole === 'Admin' ? 'member' : 'member', $usr['id']]);
            // Note: keep users.role as 'member' to preserve app semantics; team_members.role is the effective team role
        }
        sendJson(['success' => true, 'message' => 'Role updated']);
    } catch (PDOException $e) {
        sendJson(['success' => false, 'message' => 'Database error'], 500);
    }
}

/**
 * Remove member (soft remove: mark status removed)
 */
function handleRemoveMember(PDO $pdo, int $userId, array $data) {
    try {
        $email = trim($data['email'] ?? '');
        if (!$email) sendJson(['success' => false, 'message' => 'Email required'], 422);
        $ctx = getOwnerContext($pdo, $userId);
        $ownerId = $ctx['owner_id'];
        // Prevent removing owner
        $chk = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $chk->execute([$email]);
        $usr = $chk->fetch();
        if ($usr && (int)$usr['id'] === $ownerId) {
            sendJson(['success' => false, 'message' => 'Cannot remove owner'], 403);
        }
        // Soft-remove in team_members
        $pdo->prepare("UPDATE team_members SET status = 'removed', updated_at = NOW() WHERE workspace_owner_id = ? AND member_email = ?")
            ->execute([$ownerId, $email]);
        sendJson(['success' => true, 'message' => 'Member removed']);
    } catch (PDOException $e) {
        sendJson(['success' => false, 'message' => 'Database error'], 500);
    }
}

/**
 * Revoke pending invite
 */
function handleRevokeInvite(PDO $pdo, int $userId, array $data) {
    try {
        $ctx = getOwnerContext($pdo, $userId);
        $ownerId = $ctx['owner_id'];
        
        $email = trim($data['email'] ?? '');
        $token = trim($data['token'] ?? '');
        
        if (empty($email)) {
            sendJson(['success' => false, 'message' => 'Email is required'], 400);
        }
        
        // Delete the pending invite
        $stmt = $pdo->prepare("DELETE FROM pending_invites WHERE inviter_id = ? AND email = ?");
        $stmt->execute([$ownerId, $email]);
        
        if ($stmt->rowCount() > 0) {
            sendJson(['success' => true, 'message' => 'Invitation revoked successfully']);
        } else {
            sendJson(['success' => false, 'message' => 'Invitation not found'], 404);
        }
    } catch (PDOException $e) {
        sendJson(['success' => false, 'message' => 'Database error'], 500);
    }
}

