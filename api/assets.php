<?php
error_reporting(0);
ini_set('display_errors', 0);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
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

// Load Nextcloud Storage Service and Auth Helper
require_once dirname(__DIR__) . '/app/Services/NextcloudStorage.php';
require_once __DIR__ . '/auth_helper.php';
require_once __DIR__ . '/team_permissions.php';
use App\Services\NextcloudStorage;

// Ensure database structure is up to date
define('SUPPRESS_DB_OUTPUT', true);
require_once dirname(__DIR__) . '/database/init.php';

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
			$brandKitId = $_GET['brand_kit_id'] ?? null;
			
			// Get user info to check if they're a team member
			$stmt = $pdo->prepare("SELECT workspace_owner_id FROM users WHERE id = ?");
			$stmt->execute([$userId]);
			$userInfo = $stmt->fetch();
			$workspaceOwnerId = $userInfo['workspace_owner_id'] ?? null;
			
			// If team member, show workspace owner's assets; otherwise show own assets
			$targetUserId = $workspaceOwnerId ?? $userId;
			
			if ($brandKitId) {
				$stmt = $pdo->prepare("SELECT * FROM assets WHERE user_id = ? AND brand_kit_id = ? ORDER BY created_at DESC");
				$stmt->execute([$targetUserId, $brandKitId]);
			} else {
				$stmt = $pdo->prepare("SELECT * FROM assets WHERE user_id = ? ORDER BY created_at DESC");
				$stmt->execute([$targetUserId]);
			}
			
			$assets = $stmt->fetchAll();
			
			// Add share URLs to each asset
			$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
			
			foreach ($assets as &$asset) {
				// Generate public share link if token exists
				if (!empty($asset['share_token'])) {
					$asset['public_url'] = $baseUrl . '/public/view.php?t=' . $asset['share_token'];
				} else {
					// No token - generate one
					$shareToken = bin2hex(random_bytes(16));
					$updateStmt = $pdo->prepare("UPDATE assets SET share_token = ? WHERE id = ?");
					$updateStmt->execute([$shareToken, $asset['id']]);
					$asset['share_token'] = $shareToken;
					$asset['public_url'] = $baseUrl . '/public/view.php?t=' . $shareToken;
				}
				
				// Generate Nextcloud preview URL if file ID and etag are available
				if (!empty($asset['nextcloud_file_id']) && !empty($asset['nextcloud_etag'])) {
					$asset['preview_url'] = 'https://cloud.blacnova.net/core/preview?' . http_build_query([
						'fileId' => $asset['nextcloud_file_id'],
						'x' => 1920,
						'y' => 1080,
						'a' => 'true',
						'etag' => $asset['nextcloud_etag']
					]);
				}
				
				// Check download permissions
				$downloadCheck = checkDownloadPermission($pdo, $userId, $asset['id']);
				$asset['can_download'] = $downloadCheck['can_download'];
				$asset['requires_approval'] = $downloadCheck['requires_approval'];
				$asset['download_request_status'] = $downloadCheck['request_status'] ?? null;
			}
			
			sendJson([
				'success' => true,
				'assets' => $assets
			]);
		} catch (Exception $e) {
			sendJson(['success' => false, 'message' => 'Failed to fetch assets'], 500);
		}
		break;
		
	case 'delete':
		try {
			$assetId = $_GET['id'] ?? null;
			
			if (!$assetId) {
				sendJson(['success' => false, 'message' => 'Asset ID required'], 400);
			}
			
			// Get asset info
			$stmt = $pdo->prepare("SELECT * FROM assets WHERE id = ? AND user_id = ?");
			$stmt->execute([$assetId, $userId]);
			$asset = $stmt->fetch();
			
			if (!$asset) {
				sendJson(['success' => false, 'message' => 'Asset not found'], 404);
			}
			
			// Delete from database first
			$stmt = $pdo->prepare("DELETE FROM assets WHERE id = ? AND user_id = ?");
			$stmt->execute([$assetId, $userId]);
			
			// Delete the actual file
			$storageDriver = NextcloudStorage::getStorageDriver();
			
			if ($storageDriver === 'nextcloud' && !empty($asset['file_path'])) {
				// Delete from Nextcloud using stored path and workspace credentials
				try {
					// Get Nextcloud credentials (workspace owner's for team members)
					$ncCreds = getNextcloudCredentials($pdo, $userId);
					
					if ($ncCreds && !empty($ncCreds['nextcloud_username']) && !empty($ncCreds['nextcloud_password'])) {
						$storage = new NextcloudStorage($ncCreds['nextcloud_username'], $ncCreds['nextcloud_password']);
						$remotePath = $asset['file_path'];
					
						$deleteResult = $storage->deleteFile($remotePath);
						
						if (!$deleteResult['success']) {
							error_log('Nextcloud deletion warning: ' . $deleteResult['message']);
						}
					} else {
						error_log('Nextcloud credentials not found for user ' . $userId);
					}
				} catch (Exception $e) {
					error_log('Nextcloud deletion error: ' . $e->getMessage());
				}
			} elseif ($storageDriver === 'local' && !empty($asset['file_path'])) {
				// Delete from local storage
				$fullPath = dirname(__DIR__) . $asset['file_path'];
				if (is_file($fullPath)) {
					@unlink($fullPath);
				}
			}
			
			sendJson(['success' => true, 'message' => 'Asset deleted']);
		} catch (Exception $e) {
			sendJson(['success' => false, 'message' => 'Failed to delete asset'], 500);
		}
		break;
		
	default:
		sendJson(['success' => false, 'message' => 'Invalid action'], 400);
}


