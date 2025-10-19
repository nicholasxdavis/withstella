<?php
/**
 * Public Shares API
 * Handles public sharing of assets
 */

error_reporting(0);
ini_set('display_errors', 0);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-User-ID, X-User-Email');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

function sendJson($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit();
}

// Load Auth Helper and Team Permissions
require_once __DIR__ . '/auth_helper.php';
require_once __DIR__ . '/team_permissions.php';

// Database connection
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

$action = $_GET['action'] ?? 'list';

// Get authenticated user ID
try {
    $userId = getAuthenticatedUserId($pdo);
} catch (Exception $e) {
    sendJson(['success' => false, 'message' => $e->getMessage()], 401);
}

switch ($action) {
    case 'list':
        try {
            // Get user info to check if they're a team member
            $stmt = $pdo->prepare("SELECT workspace_owner_id FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $userInfo = $stmt->fetch();
            $workspaceOwnerId = $userInfo['workspace_owner_id'] ?? null;
            
            // If team member, show workspace owner's shares; otherwise show own
            $targetUserId = $workspaceOwnerId ?? $userId;
            
            $stmt = $pdo->prepare("
                SELECT ps.*, a.name as asset_name, a.type as asset_type,
                       u.full_name as created_by_name
                FROM public_shares ps
                JOIN assets a ON ps.asset_id = a.id
                JOIN users u ON ps.created_by = u.id
                WHERE a.user_id = ?
                ORDER BY ps.created_at DESC
            ");
            $stmt->execute([$targetUserId]);
            $shares = $stmt->fetchAll();
            
            // Add public URLs
            $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
            
            foreach ($shares as &$share) {
                $share['public_url'] = $baseUrl . '/public/view.php?share=' . $share['share_token'];
                $share['is_expired'] = $share['expires_at'] && strtotime($share['expires_at']) < time();
            }
            
            sendJson([
                'success' => true,
                'shares' => $shares
            ]);
        } catch (Exception $e) {
            sendJson(['success' => false, 'message' => 'Failed to fetch public shares'], 500);
        }
        break;
        
    case 'create':
        try {
            // Check if user has permission to manage shares
            if (!hasTeamPermission($pdo, $userId, 'manage_shares')) {
                sendJson(['success' => false, 'message' => 'You do not have permission to create public shares'], 403);
            }
            
            $assetId = $_POST['asset_id'] ?? null;
            $expiresAt = $_POST['expires_at'] ?? null;
            
            if (!$assetId) {
                sendJson(['success' => false, 'message' => 'Asset ID is required'], 400);
            }
            
            // Verify asset ownership (through workspace)
            $stmt = $pdo->prepare("
                SELECT a.id, a.user_id, u.workspace_owner_id
                FROM assets a
                JOIN users u ON a.user_id = u.id
                WHERE a.id = ?
            ");
            $stmt->execute([$assetId]);
            $asset = $stmt->fetch();
            
            if (!$asset) {
                sendJson(['success' => false, 'message' => 'Asset not found'], 404);
            }
            
            // Check if user has access to this asset
            $userStmt = $pdo->prepare("SELECT workspace_owner_id FROM users WHERE id = ?");
            $userStmt->execute([$userId]);
            $userInfo = $userStmt->fetch();
            $workspaceOwnerId = $userInfo['workspace_owner_id'] ?? null;
            
            if ($workspaceOwnerId && $asset['user_id'] !== $workspaceOwnerId) {
                sendJson(['success' => false, 'message' => 'You do not have access to this asset'], 403);
            } elseif (!$workspaceOwnerId && $asset['user_id'] !== $userId) {
                sendJson(['success' => false, 'message' => 'You do not have access to this asset'], 403);
            }
            
            // Check if share already exists and is active
            $stmt = $pdo->prepare("
                SELECT id FROM public_shares 
                WHERE asset_id = ? AND is_active = 1 
                AND (expires_at IS NULL OR expires_at > NOW())
            ");
            $stmt->execute([$assetId]);
            $existingShare = $stmt->fetch();
            
            if ($existingShare) {
                sendJson(['success' => false, 'message' => 'Active public share already exists for this asset'], 400);
            }
            
            // Generate unique share token
            $shareToken = bin2hex(random_bytes(32));
            
            // Create public share
            $stmt = $pdo->prepare("
                INSERT INTO public_shares (asset_id, share_token, expires_at, created_by)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$assetId, $shareToken, $expiresAt, $userId]);
            $shareId = $pdo->lastInsertId();
            
            $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
            
            sendJson([
                'success' => true,
                'message' => 'Public share created successfully',
                'share' => [
                    'id' => $shareId,
                    'share_token' => $shareToken,
                    'public_url' => $baseUrl . '/public/view.php?share=' . $shareToken,
                    'expires_at' => $expiresAt
                ]
            ]);
        } catch (Exception $e) {
            sendJson(['success' => false, 'message' => 'Failed to create public share'], 500);
        }
        break;
        
    case 'deactivate':
        try {
            // Check if user has permission to manage shares
            if (!hasTeamPermission($pdo, $userId, 'manage_shares')) {
                sendJson(['success' => false, 'message' => 'You do not have permission to manage public shares'], 403);
            }
            
            $shareId = $_POST['share_id'] ?? null;
            
            if (!$shareId) {
                sendJson(['success' => false, 'message' => 'Share ID is required'], 400);
            }
            
            // Verify share ownership
            $stmt = $pdo->prepare("
                SELECT ps.id, a.user_id, u.workspace_owner_id
                FROM public_shares ps
                JOIN assets a ON ps.asset_id = a.id
                JOIN users u ON a.user_id = u.id
                WHERE ps.id = ?
            ");
            $stmt->execute([$shareId]);
            $share = $stmt->fetch();
            
            if (!$share) {
                sendJson(['success' => false, 'message' => 'Share not found'], 404);
            }
            
            // Check if user has access to this share
            $userStmt = $pdo->prepare("SELECT workspace_owner_id FROM users WHERE id = ?");
            $userStmt->execute([$userId]);
            $userInfo = $userStmt->fetch();
            $workspaceOwnerId = $userInfo['workspace_owner_id'] ?? null;
            
            if ($workspaceOwnerId && $share['user_id'] !== $workspaceOwnerId) {
                sendJson(['success' => false, 'message' => 'You do not have access to this share'], 403);
            } elseif (!$workspaceOwnerId && $share['user_id'] !== $userId) {
                sendJson(['success' => false, 'message' => 'You do not have access to this share'], 403);
            }
            
            // Deactivate share
            $stmt = $pdo->prepare("UPDATE public_shares SET is_active = 0 WHERE id = ?");
            $stmt->execute([$shareId]);
            
            sendJson(['success' => true, 'message' => 'Public share deactivated successfully']);
        } catch (Exception $e) {
            sendJson(['success' => false, 'message' => 'Failed to deactivate public share'], 500);
        }
        break;
        
    case 'stats':
        try {
            // Get user info to check if they're a team member
            $stmt = $pdo->prepare("SELECT workspace_owner_id FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $userInfo = $stmt->fetch();
            $workspaceOwnerId = $userInfo['workspace_owner_id'] ?? null;
            
            // If team member, show workspace owner's stats; otherwise show own
            $targetUserId = $workspaceOwnerId ?? $userId;
            
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(*) as total_shares,
                    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_shares,
                    SUM(download_count) as total_downloads
                FROM public_shares ps
                JOIN assets a ON ps.asset_id = a.id
                WHERE a.user_id = ?
            ");
            $stmt->execute([$targetUserId]);
            $stats = $stmt->fetch();
            
            sendJson([
                'success' => true,
                'stats' => $stats
            ]);
        } catch (Exception $e) {
            sendJson(['success' => false, 'message' => 'Failed to fetch share statistics'], 500);
        }
        break;
        
    default:
        sendJson(['success' => false, 'message' => 'Invalid action'], 400);
        break;
}
?>


