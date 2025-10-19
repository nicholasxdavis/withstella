<?php
/**
 * Download Requests API
 * Handles download approval system for team members
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

// Get authenticated user ID with fallback to session
try {
    $userId = getAuthenticatedUserId($pdo);
} catch (Exception $e) {
    // Fallback: Try to get from session
    session_start();
    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
    } else {
        sendJson(['success' => false, 'message' => 'Not authenticated. Please log in.'], 401);
    }
}

switch ($action) {
    case 'list':
        try {
            // Get user info to determine if they're a workspace owner
            $stmt = $pdo->prepare("SELECT workspace_owner_id FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $userInfo = $stmt->fetch();
            $isWorkspaceOwner = !$userInfo['workspace_owner_id'];
            
            if ($isWorkspaceOwner) {
                // Workspace owner sees all requests for their assets
                $stmt = $pdo->prepare("
                    SELECT dr.*, a.name as asset_name, a.type as asset_type,
                           u.full_name as requester_name, u.email as requester_email,
                           reviewer.full_name as reviewer_name
                    FROM download_requests dr
                    JOIN assets a ON dr.asset_id = a.id
                    JOIN users u ON dr.requester_id = u.id
                    LEFT JOIN users reviewer ON dr.reviewed_by = reviewer.id
                    WHERE a.user_id = ?
                    ORDER BY dr.requested_at DESC
                ");
                $stmt->execute([$userId]);
            } else {
                // Team member sees only their own requests
                $stmt = $pdo->prepare("
                    SELECT dr.*, a.name as asset_name, a.type as asset_type,
                           u.full_name as requester_name, u.email as requester_email,
                           reviewer.full_name as reviewer_name
                    FROM download_requests dr
                    JOIN assets a ON dr.asset_id = a.id
                    JOIN users u ON dr.requester_id = u.id
                    LEFT JOIN users reviewer ON dr.reviewed_by = reviewer.id
                    WHERE dr.requester_id = ?
                    ORDER BY dr.requested_at DESC
                ");
                $stmt->execute([$userId]);
            }
            
            $requests = $stmt->fetchAll();
            
            sendJson([
                'success' => true,
                'requests' => $requests,
                'is_workspace_owner' => $isWorkspaceOwner
            ]);
        } catch (Exception $e) {
            sendJson(['success' => false, 'message' => 'Failed to fetch download requests'], 500);
        }
        break;
        
    case 'create':
        try {
            $assetId = $_POST['asset_id'] ?? null;
            
            if (!$assetId) {
                sendJson(['success' => false, 'message' => 'Asset ID is required'], 400);
            }
            
            // Check if user can download directly
            $downloadCheck = checkDownloadPermission($pdo, $userId, $assetId);
            
            if ($downloadCheck['can_download']) {
                sendJson(['success' => false, 'message' => 'You already have permission to download this asset'], 400);
            }
            
            if (!$downloadCheck['requires_approval']) {
                sendJson(['success' => false, 'message' => 'You do not have permission to request downloads'], 403);
            }
            
            // Create download request
            $result = createDownloadRequest($pdo, $userId, $assetId);
            
            if ($result['success']) {
                sendJson([
                    'success' => true,
                    'message' => 'Download request created successfully',
                    'request_id' => $result['request_id']
                ]);
            } else {
                sendJson(['success' => false, 'message' => $result['message']], 400);
            }
        } catch (Exception $e) {
            sendJson(['success' => false, 'message' => 'Failed to create download request'], 500);
        }
        break;
        
    case 'approve':
        try {
            $requestId = $_POST['request_id'] ?? null;
            $notes = $_POST['notes'] ?? '';
            
            if (!$requestId) {
                sendJson(['success' => false, 'message' => 'Request ID is required'], 400);
            }
            
            // Check if user is workspace owner
            $stmt = $pdo->prepare("SELECT workspace_owner_id FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $userInfo = $stmt->fetch();
            
            if ($userInfo['workspace_owner_id']) {
                sendJson(['success' => false, 'message' => 'Only workspace owners can approve download requests'], 403);
            }
            
            // Update request status
            $stmt = $pdo->prepare("
                UPDATE download_requests 
                SET status = 'approved', reviewed_at = NOW(), reviewed_by = ?, notes = ?
                WHERE id = ? AND status = 'pending'
            ");
            $stmt->execute([$userId, $notes, $requestId]);
            
            if ($stmt->rowCount() > 0) {
                sendJson(['success' => true, 'message' => 'Download request approved']);
            } else {
                sendJson(['success' => false, 'message' => 'Request not found or already processed'], 404);
            }
        } catch (Exception $e) {
            sendJson(['success' => false, 'message' => 'Failed to approve download request'], 500);
        }
        break;
        
    case 'deny':
        try {
            $requestId = $_POST['request_id'] ?? null;
            $notes = $_POST['notes'] ?? '';
            
            if (!$requestId) {
                sendJson(['success' => false, 'message' => 'Request ID is required'], 400);
            }
            
            // Check if user is workspace owner
            $stmt = $pdo->prepare("SELECT workspace_owner_id FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $userInfo = $stmt->fetch();
            
            if ($userInfo['workspace_owner_id']) {
                sendJson(['success' => false, 'message' => 'Only workspace owners can deny download requests'], 403);
            }
            
            // Update request status
            $stmt = $pdo->prepare("
                UPDATE download_requests 
                SET status = 'denied', reviewed_at = NOW(), reviewed_by = ?, notes = ?
                WHERE id = ? AND status = 'pending'
            ");
            $stmt->execute([$userId, $notes, $requestId]);
            
            if ($stmt->rowCount() > 0) {
                sendJson(['success' => true, 'message' => 'Download request denied']);
            } else {
                sendJson(['success' => false, 'message' => 'Request not found or already processed'], 404);
            }
        } catch (Exception $e) {
            sendJson(['success' => false, 'message' => 'Failed to deny download request'], 500);
        }
        break;
        
    case 'check':
        try {
            $assetId = $_GET['asset_id'] ?? null;
            
            if (!$assetId) {
                sendJson(['success' => false, 'message' => 'Asset ID is required'], 400);
            }
            
            $downloadCheck = checkDownloadPermission($pdo, $userId, $assetId);
            
            sendJson([
                'success' => true,
                'can_download' => $downloadCheck['can_download'],
                'requires_approval' => $downloadCheck['requires_approval'],
                'request_status' => $downloadCheck['request_status'] ?? null
            ]);
        } catch (Exception $e) {
            sendJson(['success' => false, 'message' => 'Failed to check download permission'], 500);
        }
        break;
        
    default:
        sendJson(['success' => false, 'message' => 'Invalid action'], 400);
        break;
}
?>

