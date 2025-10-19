<?php
error_reporting(0);
ini_set('display_errors', 0);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
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

// Helpers
function getBaseUrl() {
	$scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
	$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
	return $scheme . '://' . $host;
}

function ensureDirectory($path) {
	if (!is_dir($path)) {
		mkdir($path, 0755, true);
	}
	return is_dir($path) && is_writable($path);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	try {
		// Get authenticated user ID
		try {
			$userId = getAuthenticatedUserId($pdo);
		} catch (Exception $e) {
			sendJson(['success' => false, 'message' => $e->getMessage()], 401);
		}
		
		// Check if user has permission to upload assets
		if (!hasTeamPermission($pdo, $userId, 'upload_assets')) {
			sendJson(['success' => false, 'message' => 'You do not have permission to upload assets'], 403);
		}
		
		// Get Nextcloud credentials (uses workspace owner's credentials for team members)
		$ncCreds = getNextcloudCredentials($pdo, $userId);
		$effectiveUserId = ($ncCreds && !empty($ncCreds['is_team_member']) && !empty($ncCreds['workspace_owner_id']))
			? (int)$ncCreds['workspace_owner_id']
			: (int)$userId;
		
		if (!$ncCreds || empty($ncCreds['nextcloud_username']) || empty($ncCreds['nextcloud_password'])) {
			sendJson(['success' => false, 'message' => 'Nextcloud account not configured'], 500);
		}
		
		if (!isset($_FILES['file'])) {
			sendJson(['success' => false, 'message' => 'No file uploaded'], 400);
		}

		$file = $_FILES['file'];
		$type = $_POST['type'] ?? 'asset'; // asset, logo, brand-kit
		$brandKitId = $_POST['brand_kit_id'] ?? null;
		$name = $_POST['name'] ?? $file['name'];

		if ($file['error'] !== UPLOAD_ERR_OK) {
			sendJson(['success' => false, 'message' => 'File upload error'], 400);
		}
		
		// 🔒 CHECK PLAN LIMIT - Storage
		require_once __DIR__ . '/plan_limits.php';
		$planLimits = new PlanLimits($pdo);
		$fileSize = $file['size'];
		$canUpload = $planLimits->canUploadFile($effectiveUserId, $fileSize);
		if (!$canUpload['allowed']) {
			sendJson([
				'success' => false,
				'message' => $canUpload['message'],
				'storage_limit_reached' => true,
				'current_gb' => $canUpload['current_gb'],
				'after_upload_gb' => $canUpload['after_upload_gb'],
				'limit_gb' => $canUpload['limit_gb'],
				'upgrade_required' => true
			], 403);
		}

		// All files go to the same 'assets' folder in Nextcloud
		$folder = 'assets';

		$originalName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['name']);
		$uniqueName = $type . '_' . time() . '_' . $originalName;
		
		// Get storage driver
		$storageDriver = NextcloudStorage::getStorageDriver();
		$fileUrl = '';
		$publicPath = '';
		$shareUrl = null;
		
		if ($storageDriver === 'nextcloud') {
			// Upload to Nextcloud using workspace credentials (owner's for team members)
			try {
				$storage = new NextcloudStorage($ncCreds['nextcloud_username'], $ncCreds['nextcloud_password']);
				$remotePath = $folder . '/' . $uniqueName;
				$uploadResult = $storage->uploadFile($file['tmp_name'], $remotePath);
				
				if (!$uploadResult['success']) {
					sendJson(['success' => false, 'message' => 'Nextcloud upload failed: ' . $uploadResult['message']], 500);
				}
				
				// Get URLs from upload result
				$shareUrl = $uploadResult['share_url'] ?? null;
				$webdavUrl = $uploadResult['url'];
				$publicPath = $uploadResult['path'];
				
				// Store the internal file path for later access
				// This is what we'll use for image proxy and deletion
				$fileUrl = $publicPath; // Store internal path, not public URL
				
				// Note: We don't create public shares anymore due to password requirements
				// Instead, we'll use the image proxy for access control
				// This is more secure anyway!
				
			} catch (Exception $e) {
				sendJson(['success' => false, 'message' => 'Nextcloud error: ' . $e->getMessage()], 500);
			}
		} else {
			// Local storage (fallback)
			$rootDir = dirname(__DIR__);
			$uploadsRoot = $rootDir . '/uploads';
			$targetDir = $uploadsRoot . '/' . $folder;

			if (!ensureDirectory($targetDir)) {
				sendJson(['success' => false, 'message' => 'Failed to prepare upload directory'], 500);
			}

			$targetPath = $targetDir . '/' . $uniqueName;

			if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
				sendJson(['success' => false, 'message' => 'Failed to save uploaded file'], 500);
			}

			$publicPath = '/uploads/' . $folder . '/' . $uniqueName;
			$fileUrl = getBaseUrl() . $publicPath;
		}

		// Generate unique share token for public access
		$shareToken = bin2hex(random_bytes(16)); // 32-character token
		
		// Get file info from Nextcloud to get file ID and etag
		$fileInfo = null;
		if ($storageDriver === 'nextcloud' && !empty($publicPath)) {
			try {
				$storage = new NextcloudStorage($ncCreds['nextcloud_username'], $ncCreds['nextcloud_password']);
				$fileInfo = $storage->getFileInfo($publicPath);
			} catch (Exception $e) {
				// Continue without file info if it fails
			}
		}
		
		// Save metadata to database
		$stmt = $pdo->prepare("INSERT INTO assets (user_id, brand_kit_id, name, type, file_url, file_path, share_token, file_size, nextcloud_file_id, nextcloud_etag, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
		$stmt->execute([
			$effectiveUserId,
			$brandKitId,
			$name,
			$type,
			$fileUrl,
			$publicPath,
			$shareToken,
			$file['size'],
			$fileInfo['file_id'] ?? null,
			$fileInfo['etag'] ?? null
		]);

		$assetId = $pdo->lastInsertId();

		// Build URLs for frontend
		$baseAppUrl = getBaseUrl();
		
		// Generate Nextcloud preview URL if file info is available
		$previewUrl = null;
		if ($fileInfo && $fileInfo['file_id']) {
			$baseUrl = rtrim($ncCreds['nextcloud_url'] ?? 'https://cloud.blacnova.net', '/');
			$previewUrl = $baseUrl . '/core/preview?' . http_build_query([
				'fileId' => $fileInfo['file_id'],
				'x' => 1920,
				'y' => 1080,
				'a' => 'true',
				'etag' => $fileInfo['etag'] ?? ''
			]);
		}
		
		$response = [
			'success' => true,
			'storage_driver' => $storageDriver,
			'asset' => [
				'id' => (int)$assetId,
				'name' => $name,
				'type' => $type,
				'url' => $baseAppUrl . '/public/view.php?t=' . $shareToken, // Public share link
				'download_url' => $baseAppUrl . '/api/image_proxy.php?id=' . $assetId . '&download=true', // Authenticated download
				'preview_url' => $previewUrl ?: ($baseAppUrl . '/api/image_proxy.php?id=' . $assetId), // Nextcloud preview or fallback
				'share_token' => $shareToken,
				'size' => $file['size'],
				'path' => $publicPath,
				'internal_path' => $fileUrl,
				'nextcloud_file_id' => $fileInfo['file_id'] ?? null,
				'nextcloud_etag' => $fileInfo['etag'] ?? null
			]
		];

		sendJson($response);

	} catch (Exception $e) {
		sendJson(['success' => false, 'message' => 'Upload failed: ' . $e->getMessage()], 500);
	}
} else {
	sendJson(['success' => false, 'message' => 'Method not allowed'], 405);
}
