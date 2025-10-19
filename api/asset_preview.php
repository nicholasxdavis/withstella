<?php
error_reporting(0);
ini_set('display_errors', 0);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
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

// Load services
require_once dirname(__DIR__) . '/app/Services/NextcloudStorage.php';
require_once __DIR__ . '/auth_helper.php';
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

// Get authenticated user ID
try {
	$userId = getAuthenticatedUserId($pdo);
} catch (Exception $e) {
	sendJson(['success' => false, 'message' => $e->getMessage()], 401);
}

$assetId = $_GET['id'] ?? null;

if (!$assetId) {
	sendJson(['success' => false, 'message' => 'Asset ID required'], 400);
}

try {
    // Get asset info, allowing team member to access owner's asset via brand_kit linkage
    $stmt = $pdo->prepare("SELECT a.* FROM assets a WHERE a.id = ?");
    $stmt->execute([$assetId]);
    $asset = $stmt->fetch();
	
	if (!$asset) {
		sendJson(['success' => false, 'message' => 'Asset not found'], 404);
	}
	
    // Determine credentials: if caller is a team member, use workspace owner creds
    $stmt = $pdo->prepare("SELECT workspace_owner_id, nextcloud_username, nextcloud_password FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $caller = $stmt->fetch();
    $ownerId = $caller['workspace_owner_id'] ?? null;
    if ($ownerId) {
        $stmt = $pdo->prepare("SELECT nextcloud_username, nextcloud_password FROM users WHERE id = ?");
        $stmt->execute([$ownerId]);
        $userCreds = $stmt->fetch();
    } else {
        $userCreds = [
            'nextcloud_username' => $caller['nextcloud_username'] ?? null,
            'nextcloud_password' => $caller['nextcloud_password'] ?? null
        ];
    }
	
	if (!$userCreds || empty($userCreds['nextcloud_username'])) {
		sendJson(['success' => false, 'message' => 'Nextcloud account not configured'], 500);
	}
	
	// Get preview URL if available
	$previewUrl = null;
	if (!empty($asset['file_path'])) {
		try {
			$storage = new NextcloudStorage($userCreds['nextcloud_username'], $userCreds['nextcloud_password']);
			$previewUrl = $storage->getPreviewUrlFromPath($asset['file_path'], 512, 512);
		} catch (Exception $e) {
			error_log('Preview URL generation failed: ' . $e->getMessage());
		}
	}
	
	// Fallback to download URL
	if (!$previewUrl && !empty($asset['file_url'])) {
		if (strpos($asset['file_url'], '/s/') !== false) {
			$previewUrl = rtrim($asset['file_url'], '/') . '/download';
		} else {
			$previewUrl = $asset['file_url'];
		}
	}
	
	sendJson([
		'success' => true,
		'preview_url' => $previewUrl,
		'download_url' => !empty($asset['file_url']) && strpos($asset['file_url'], '/s/') !== false 
			? rtrim($asset['file_url'], '/') . '/download' 
			: $asset['file_url'],
		'share_url' => $asset['file_url']
	]);
	
} catch (Exception $e) {
	sendJson(['success' => false, 'message' => 'Failed to get preview'], 500);
}

