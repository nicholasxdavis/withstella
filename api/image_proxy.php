<?php
error_reporting(0);
ini_set('display_errors', 0);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(200);
	exit();
}

// Load services
require_once dirname(__DIR__) . '/app/Services/NextcloudStorage.php';
require_once __DIR__ . '/auth_helper.php';
require_once __DIR__ . '/team_permissions.php';
use App\Services\NextcloudStorage;

// Database connection
$host = getenv('DB_HOST') ?: 'mariadb-database-rgcs4ksokcww0g04wkwg4g4k';
$dbname = getenv('DB_DATABASE') ?: 'default';
$user = getenv('DB_USERNAME') ?: 'mariadb';
$pass = getenv('DB_PASSWORD') ?: 'ba55Ko1lA8FataxMYnpl9qVploHFJXZKqCvfnwrlcxvISIqbQusX4qFeELhdYPdO';

try {
	$pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
	$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
	http_response_code(500);
	exit('Database connection failed');
}

// Get asset ID and parameters
$assetId = $_GET['id'] ?? null;
$shareToken = $_GET['t'] ?? null;
$size = isset($_GET['size']) ? (int)$_GET['size'] : 512;
$download = isset($_GET['download']) && $_GET['download'] === 'true';

if (!$assetId && !$shareToken) {
	http_response_code(400);
	exit('Asset ID or share token required');
}

try {
	$asset = null;
	$userCreds = null;
	
	// If share token provided, use public access
	if ($shareToken) {
		$stmt = $pdo->prepare("SELECT a.*, u.nextcloud_username, u.nextcloud_password 
		                       FROM assets a 
		                       JOIN users u ON a.user_id = u.id 
		                       WHERE a.share_token = ?");
		$stmt->execute([$shareToken]);
		$asset = $stmt->fetch();
		
		if ($asset) {
			$userCreds = [
				'nextcloud_username' => $asset['nextcloud_username'],
				'nextcloud_password' => $asset['nextcloud_password']
			];
		}
	} 
	// Otherwise use authenticated access
	else {
		// Get authenticated user ID
		$userId = getAuthenticatedUserId($pdo);
		
		// Get asset info - check user owns it OR has team access
		$stmt = $pdo->prepare("SELECT a.*, u.nextcloud_username, u.nextcloud_password,
		                             owner.nextcloud_username as owner_nc_username,
		                             owner.nextcloud_password as owner_nc_password
		                       FROM assets a 
		                       JOIN users u ON a.user_id = u.id
		                       LEFT JOIN users owner ON u.workspace_owner_id = owner.id
		                       WHERE a.id = ? AND (a.user_id = ? OR u.workspace_owner_id = a.user_id)");
		$stmt->execute([$assetId, $userId]);
		$asset = $stmt->fetch();
		
		if ($asset) {
			// Check download permissions if this is a download request
			if ($download) {
				$downloadCheck = checkDownloadPermission($pdo, $userId, $assetId);
				if (!$downloadCheck['can_download']) {
					http_response_code(403);
					if ($downloadCheck['requires_approval']) {
						exit('Download requires approval. Please request permission first.');
					} else {
						exit('You do not have permission to download this asset');
					}
				}
			}
			
			// Use workspace owner's credentials if team member, otherwise own credentials
			$userCreds = [
				'nextcloud_username' => $asset['owner_nc_username'] ?? $asset['nextcloud_username'],
				'nextcloud_password' => $asset['owner_nc_password'] ?? $asset['nextcloud_password']
			];
		}
	}
	
	if (!$asset) {
		http_response_code(404);
		exit('Asset not found');
	}
	
	if (!$userCreds || empty($userCreds['nextcloud_username'])) {
		http_response_code(500);
		exit('Nextcloud account not configured');
	}
	
	// Get file from Nextcloud
	if (!empty($asset['file_path'])) {
		$storage = new NextcloudStorage($userCreds['nextcloud_username'], $userCreds['nextcloud_password']);
		
		// Build WebDAV URL
		$configPath = dirname(__DIR__) . '/config/nextcloud.php';
		$config = require $configPath;
		$baseUrl = rtrim($config['url'], '/');
		$webdavPath = trim($config['webdav_path'], '/');
		$encodedUsername = rawurlencode($userCreds['nextcloud_username']);
		
		// Encode path
		$pathParts = explode('/', $asset['file_path']);
		$encodedParts = array_map('rawurlencode', $pathParts);
		$encodedPath = '/' . ltrim(implode('/', $encodedParts), '/');
		
		$fileUrl = $baseUrl . '/' . $webdavPath . '/' . $encodedUsername . $encodedPath;
		
		// Stream the file
		$ch = curl_init($fileUrl);
		curl_setopt_array($ch, [
			CURLOPT_USERPWD => $userCreds['nextcloud_username'] . ':' . $userCreds['nextcloud_password'],
			CURLOPT_RETURNTRANSFER => false,
			CURLOPT_HEADER => false,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_SSL_VERIFYPEER => true,
			CURLOPT_SSL_VERIFYHOST => 2,
			CURLOPT_TIMEOUT => 30,
			// Write to output
			CURLOPT_WRITEFUNCTION => function($ch, $data) {
				echo $data;
				return strlen($data);
			}
		]);
		
		// Set appropriate headers
		$contentType = $asset['type'] ?? 'application/octet-stream';
		header('Content-Type: ' . $contentType);
		header('Cache-Control: public, max-age=3600');
		
		// Add download header if requested
		if ($download) {
			header('Content-Disposition: attachment; filename="' . $asset['name'] . '"');
		} else {
			header('Content-Disposition: inline; filename="' . $asset['name'] . '"');
		}
		
		curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		
		if ($httpCode < 200 || $httpCode >= 300) {
			http_response_code($httpCode);
			exit('Failed to retrieve file');
		}
	} else {
		http_response_code(404);
		exit('File path not found');
	}
	
} catch (Exception $e) {
	http_response_code(500);
	exit('Error: ' . $e->getMessage());
}

