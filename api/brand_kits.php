<?php
error_reporting(0);
ini_set('display_errors', 0);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
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

// Load Auth Helper
require_once __DIR__ . '/auth_helper.php';
require_once __DIR__ . '/team_permissions.php';
require_once __DIR__ . '/plan_limits.php';
require_once dirname(__DIR__) . '/app/Services/NextcloudStorage.php';
use App\Services\NextcloudStorage;

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
			
			// If team member, show workspace owner's brand kits; otherwise show own
			$targetUserId = $workspaceOwnerId ?? $userId;
			
			$stmt = $pdo->prepare("SELECT * FROM brand_kits WHERE user_id = ? ORDER BY created_at DESC");
			$stmt->execute([$targetUserId]);
			$brandKits = $stmt->fetchAll();
			
            // For each brand kit, get associated assets and share URL
			$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
			
			foreach ($brandKits as &$kit) {
                    // Ensure kit has share token (ignore if column missing)
                    if (empty($kit['share_token'])) {
                        try {
                            $kitToken = bin2hex(random_bytes(16));
                            $pdo->prepare("UPDATE brand_kits SET share_token = ? WHERE id = ?")->execute([$kitToken, $kit['id']]);
                            $kit['share_token'] = $kitToken;
                        } catch (\PDOException $e) { /* ignore */ }
                    }
                    $kit['public_url'] = isset($kit['share_token']) ? ($baseUrl . '/public/view.php?k=' . $kit['share_token']) : null;
				$stmt = $pdo->prepare("SELECT * FROM assets WHERE brand_kit_id = ? ORDER BY created_at DESC");
				$stmt->execute([$kit['id']]);
				$kit['assets'] = $stmt->fetchAll();
				
				// Add public URLs to assets
				foreach ($kit['assets'] as &$asset) {
					if (!empty($asset['share_token'])) {
						$asset['public_url'] = $baseUrl . '/public/view.php?t=' . $asset['share_token'];
					} else {
						// Generate token if missing
						$shareToken = bin2hex(random_bytes(16));
						$updateStmt = $pdo->prepare("UPDATE assets SET share_token = ? WHERE id = ?");
                        try { $updateStmt->execute([$shareToken, $asset['id']]); } catch (\PDOException $e) { /* ignore */ }
						$asset['share_token'] = $shareToken;
						$asset['public_url'] = $baseUrl . '/public/view.php?t=' . $shareToken;
					}
                    // Add authenticated preview URL
                    $asset['preview_url'] = $baseUrl . '/api/image_proxy.php?id=' . $asset['id'];
				}
			}
			
			sendJson([
				'success' => true,
				'brand_kits' => $brandKits
			]);
		} catch (Exception $e) {
			sendJson(['success' => false, 'message' => 'Failed to fetch brand kits'], 500);
		}
		break;
		
	case 'create':
		try {
			// Check if user has permission to create brand kits
			if (!hasTeamPermission($pdo, $userId, 'create_kits')) {
				sendJson(['success' => false, 'message' => 'You do not have permission to create brand kits'], 403);
			}
			
			// Scope to workspace owner if team member
			$stmt = $pdo->prepare("SELECT workspace_owner_id FROM users WHERE id = ?");
			$stmt->execute([$userId]);
			$userInfo = $stmt->fetch();
			$targetUserId = $userInfo['workspace_owner_id'] ?? $userId;
			
			// 🔒 CHECK PLAN LIMIT - Brand Kits
			$planLimits = new PlanLimits($pdo);
			$canCreate = $planLimits->canCreateBrandKit($targetUserId);
			if (!$canCreate['allowed']) {
				sendJson([
					'success' => false,
					'message' => $canCreate['message'],
					'limit_reached' => true,
					'current' => $canCreate['current'],
					'limit' => $canCreate['limit'],
					'upgrade_required' => true
				], 403);
			}
			
			$name = $_POST['name'] ?? null;
			$description = $_POST['description'] ?? '';
			$logoAssetId = $_POST['logo_asset_id'] ?? null;
			$primaryColor = $_POST['primary_color'] ?? null;
			$secondaryColor = $_POST['secondary_color'] ?? null;
			
			if (!$name) {
				sendJson(['success' => false, 'message' => 'Brand kit name is required'], 400);
			}
			
            // Get logo source from asset if provided and copy into Nextcloud under /brand-kits/{kitId}/logo.ext
            $logoUrl = null;
            $logoRemotePath = null;
			
			$stmt = $pdo->prepare("INSERT INTO brand_kits (user_id, name, description, logo_url, primary_color, secondary_color, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
			$stmt->execute([$targetUserId, $name, $description, $logoUrl, $primaryColor, $secondaryColor]);
			
			$brandKitId = $pdo->lastInsertId();
            
            // If a logo asset was provided, copy its stored file into a kit folder in Nextcloud
            if ($logoAssetId) {
				$stmt = $pdo->prepare("SELECT file_path, file_url FROM assets WHERE id = ? AND user_id = ?");
				$stmt->execute([$logoAssetId, $targetUserId]);
                $logoAsset = $stmt->fetch();
                if ($logoAsset && !empty($logoAsset['file_path'])) {
                    try {
                        // Determine a destination path
                        $ext = pathinfo($logoAsset['file_path'], PATHINFO_EXTENSION) ?: 'png';
                        $remotePath = 'brand-kits/' . $userId . '/' . $brandKitId . '/logo.' . $ext;
                        $storageDriver = NextcloudStorage::getStorageDriver();
                        if ($storageDriver === 'nextcloud') {
                            // Copy server-side using WebDAV COPY if the file is already in Nextcloud
                            $nc = new NextcloudStorage();
                            $copy = $nc->copyFile($logoAsset['file_path'], $remotePath);
                            if ($copy['success']) {
                                $logoRemotePath = '/' . ltrim($remotePath, '/');
                                $logoUrl = $nc->getPreviewUrlFromPath($logoRemotePath, 256, 256) ?? $nc->getDownloadUrl($nc->createPublicShare($logoRemotePath));
                            }
                        }
                    } catch (\Throwable $e) {
                        // Fallback to proxy URL if Nextcloud copy fails
                        $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
                        $logoUrl = $baseUrl . '/api/image_proxy.php?id=' . $logoAssetId;
                    }
                }
                // Persist final logo_url if we have it
                if ($logoUrl) {
                    $pdo->prepare("UPDATE brand_kits SET logo_url = ?, updated_at = NOW() WHERE id = ?")->execute([$logoUrl, $brandKitId]);
                }
            }
			
            sendJson([
				'success' => true,
				'brand_kit' => [
					'id' => (int)$brandKitId,
					'name' => $name,
					'description' => $description,
					'logo_url' => $logoUrl,
					'primary_color' => $primaryColor,
					'secondary_color' => $secondaryColor,
					'assets' => []
				]
			]);
		} catch (Exception $e) {
			sendJson(['success' => false, 'message' => 'Failed to create brand kit'], 500);
		}
		break;
		
	case 'update':
		try {
			$kitId = $_POST['id'] ?? null;
			$name = $_POST['name'] ?? null;
			$description = $_POST['description'] ?? '';
			$logoAssetId = $_POST['logo_asset_id'] ?? null;
			$primaryColor = $_POST['primary_color'] ?? null;
			$secondaryColor = $_POST['secondary_color'] ?? null;
			
			// Scope to workspace owner if team member
			$stmt = $pdo->prepare("SELECT workspace_owner_id FROM users WHERE id = ?");
			$stmt->execute([$userId]);
			$userInfo = $stmt->fetch();
			$targetUserId = $userInfo['workspace_owner_id'] ?? $userId;
			
			if (!$kitId) {
				sendJson(['success' => false, 'message' => 'Brand kit ID required'], 400);
			}
			
			// Verify ownership
			$stmt = $pdo->prepare("SELECT * FROM brand_kits WHERE id = ? AND user_id = ?");
			$stmt->execute([$kitId, $targetUserId]);
			$kit = $stmt->fetch();
			
			if (!$kit) {
				sendJson(['success' => false, 'message' => 'Brand kit not found'], 404);
			}
			
			// Build update query dynamically
			$updates = [];
			$params = [];
			
			if ($name !== null) {
				$updates[] = "name = ?";
				$params[] = $name;
			}
			if ($description !== null) {
				$updates[] = "description = ?";
				$params[] = $description;
			}
			if ($logoAssetId !== null) {
				// Get logo URL from asset (use proxy URL)
				$stmt = $pdo->prepare("SELECT id FROM assets WHERE id = ? AND user_id = ?");
				$stmt->execute([$logoAssetId, $targetUserId]);
				$logoAsset = $stmt->fetch();
				if ($logoAsset) {
					$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
					$logoUrl = $baseUrl . '/api/image_proxy.php?id=' . $logoAssetId;
				} else {
					$logoUrl = null;
				}
				$updates[] = "logo_url = ?";
				$params[] = $logoUrl;
			}
			if ($primaryColor !== null) {
				$updates[] = "primary_color = ?";
				$params[] = $primaryColor;
			}
			if ($secondaryColor !== null) {
				$updates[] = "secondary_color = ?";
				$params[] = $secondaryColor;
			}
			
			if (!empty($updates)) {
				$updates[] = "updated_at = NOW()";
				$params[] = $kitId;
				$params[] = $targetUserId;
				
				$sql = "UPDATE brand_kits SET " . implode(', ', $updates) . " WHERE id = ? AND user_id = ?";
				$stmt = $pdo->prepare($sql);
				$stmt->execute($params);
			}
			
			sendJson(['success' => true, 'message' => 'Brand kit updated']);
		} catch (Exception $e) {
			sendJson(['success' => false, 'message' => 'Failed to update brand kit'], 500);
		}
		break;
		
	case 'delete':
		try {
			$kitId = $_GET['id'] ?? null;
			
			if (!$kitId) {
				sendJson(['success' => false, 'message' => 'Brand kit ID required'], 400);
			}
			
			// Verify ownership (scope to owner if member)
			$stmt = $pdo->prepare("SELECT workspace_owner_id FROM users WHERE id = ?");
			$stmt->execute([$userId]);
			$userInfo = $stmt->fetch();
			$targetUserId = $userInfo['workspace_owner_id'] ?? $userId;
			
			$stmt = $pdo->prepare("SELECT * FROM brand_kits WHERE id = ? AND user_id = ?");
			$stmt->execute([$kitId, $targetUserId]);
			$kit = $stmt->fetch();
			
			if (!$kit) {
				sendJson(['success' => false, 'message' => 'Brand kit not found'], 404);
			}
			
			// Delete the brand kit (assets will have brand_kit_id set to NULL due to ON DELETE SET NULL)
			$stmt = $pdo->prepare("DELETE FROM brand_kits WHERE id = ? AND user_id = ?");
			$stmt->execute([$kitId, $targetUserId]);
			
			sendJson(['success' => true, 'message' => 'Brand kit deleted']);
		} catch (Exception $e) {
			sendJson(['success' => false, 'message' => 'Failed to delete brand kit'], 500);
		}
		break;
		
	default:
		sendJson(['success' => false, 'message' => 'Invalid action'], 400);
}

